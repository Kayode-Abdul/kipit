<?php

namespace App\Console\Commands;

use App\Helpers\DesktopHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Step-by-step verification that desktop sync can pull 200,000+ rows.
 *
 * Usage:
 *   php artisan sync:stress-pull
 *   php artisan sync:stress-pull --target=200000
 */
class StressTestSyncPull extends Command
{
    protected $signature = 'sync:stress-pull
        {--target=200000 : Minimum rows the pull stream must deliver}
        {--batch=2000 : Pull batch size}
        {--max-batches-per-run=20 : Batches per sync:run (forces resume)}';

    protected $description = 'Step-by-step stress test: desktop sync pull of 200k+ rows with cursor resume';

    public function handle(): int
    {
        $target = max(1, (int) $this->option('target'));
        $batchSize = max(50, min(2000, (int) $this->option('batch')));
        $maxBatches = max(1, (int) $this->option('max-batches-per-run'));
        $total = $target + $batchSize;
        $totalPages = (int) ceil($total / $batchSize);

        $this->info('=== Desktop sync pull stress test ===');
        $this->line("Target: {$target}+ rows | Batch: {$batchSize} | Pages: {$totalPages} | Max batches/run: {$maxBatches}");

        // --- Step 0: isolated local SQLite (desktop-like) ---
        $this->newLine();
        $this->info('Step 0/5: Create isolated local SQLite DB (desktop-like)');
        $sqlitePath = storage_path('app/sync-stress-'.getmypid().'.sqlite');
        if (file_exists($sqlitePath)) {
            @unlink($sqlitePath);
        }
        touch($sqlitePath);

        config([
            'database.default' => 'sync_stress',
            'database.connections.sync_stress' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'sync.assume_online' => true,
            'sync.batch_size' => $batchSize,
            'sync.max_pull_batches_per_run' => $maxBatches,
            'sync.http_timeout' => 60,
        ]);
        DB::purge('sync_stress');
        DB::reconnect('sync_stress');
        DB::setDefaultConnection('sync_stress');

        Schema::create('members', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->uuid('sync_id')->nullable()->unique();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
        $this->line("  SQLite: {$sqlitePath}");

        // --- Step 1: desktop session config (logged-in sync token) ---
        $this->newLine();
        $this->info('Step 1/5: Simulate logged-in desktop sync credentials');
        $serverBase = 'https://sync-stress.local/api/sync';
        DesktopHelper::storeServerConfig($serverBase, 'stress-desktop-token');
        $resolvedUrl = DesktopHelper::getServerUrl();
        $resolvedToken = DesktopHelper::getSyncToken();
        $this->line('  server_url + Bearer sync_token stored (desktop login equivalent).');
        $this->line('  resolved url='.($resolvedUrl ?: 'null').' token='.($resolvedToken ? 'set' : 'missing'));
        if ($resolvedUrl !== $serverBase || $resolvedToken !== 'stress-desktop-token') {
            $this->error('  DesktopHelper did not persist stress credentials.');

            return self::FAILURE;
        }

        // --- Step 2: confirm empty local shop data ---
        $this->newLine();
        $this->info('Step 2/5: Local members table empty and ready for pull merge');
        $this->line('  members count: '.DB::table('members')->count());

        // --- Step 3: fake live pull stream of 200k+ ---
        $this->newLine();
        $this->info('Step 3/5: Fake live server pull stream (200k+ cursor pages)');
        $page = 0;
        $recordsSeen = 0;

        Http::fake(function (\Illuminate\Http\Client\Request $request) use (
            &$page,
            &$recordsSeen,
            $serverBase,
            $batchSize,
            $total,
            $totalPages
        ) {
            if ($request->method() !== 'GET') {
                return Http::response(['status' => 'success'], 200);
            }

            if (! str_contains($request->url(), '/pull')) {
                return Http::response(['status' => 'error', 'message' => 'unexpected url: '.$request->url()], 404);
            }

            $auth = $request->header('Authorization')[0] ?? '';
            if (! str_contains($auth, 'Bearer')) {
                return Http::response(['status' => 'error', 'message' => 'unauthorized'], 401);
            }

            $page++;
            $start = ($page - 1) * $batchSize;
            $count = max(0, min($batchSize, $total - $start));
            $recordsSeen += $count;
            $hasMore = $page < $totalPages;
            $now = now()->toDateTimeString();
            $records = [];
            for ($i = 0; $i < $count; $i++) {
                $n = $start + $i + 1;
                $records[] = [
                    'sync_id' => sprintf('stress-%07d', $n),
                    'name' => 'Stress '.$n,
                    'code' => sprintf('H%07d', $n),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'is_synced' => true,
                ];
            }

            return Http::response([
                'status' => 'success',
                'sync_data' => ['members' => $records],
                'server_time' => $now,
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? 'members:'.($start + $count) : null,
            ], 200);
        });
        $this->line("  Stream configured for {$total} rows across {$totalPages} pages.");

        // --- Step 4: run sync:run until cursor clears ---
        $this->newLine();
        $this->info('Step 4/5: Run sync:run until backlog is fully consumed (resume)');
        Cache::forget('sync_pull_cursor');
        Cache::forget('sync_pull_pending_server_time');
        Cache::forget('last_successful_pull_time');

        $runs = 0;
        $maxRuns = (int) ceil($totalPages / $maxBatches) + 5;
        $started = microtime(true);

        do {
            $runs++;
            $this->line("  → sync:run #{$runs} (pages so far: {$page}, rows seen: {$recordsSeen})");

            // Keep stress connection as default inside nested artisan call.
            DB::setDefaultConnection('sync_stress');
            Artisan::call('sync:run');
            $out = trim(Artisan::output());
            if ($out !== '') {
                foreach (preg_split("/\r\n|\n|\r/", $out) as $line) {
                    if ($line !== '') {
                        $this->line('     '.$line);
                    }
                }
            }

            $done = Cache::get('sync_pull_cursor') === null && Cache::has('last_successful_pull_time');
            if ($done) {
                break;
            }
            if ($runs >= $maxRuns) {
                $this->error('  Failed: exceeded max sync runs without completing.');
                @unlink($sqlitePath);

                return self::FAILURE;
            }
        } while (true);

        $elapsed = round(microtime(true) - $started, 2);

        // --- Step 5: assert ---
        $this->newLine();
        $this->info('Step 5/5: Assert 200k+ pull completed');
        DB::setDefaultConnection('sync_stress');
        $merged = DB::table('members')->count();

        $ok = $recordsSeen >= $target
            && $merged >= $target
            && $runs > 1
            && Cache::get('sync_pull_cursor') === null;

        $this->table(
            ['Check', 'Result'],
            [
                ['Auth token accepted on pull', 'yes (Bearer required)'],
                ['Rows streamed from live pull', number_format($recordsSeen)],
                ['Rows merged into local SQLite', number_format($merged)],
                ['sync:run cycles (resume)', (string) $runs],
                ['Pull pages', (string) $page],
                ['Cursor cleared', Cache::get('sync_pull_cursor') === null ? 'yes' : 'no'],
                ['Elapsed seconds', (string) $elapsed],
                ['Target met (>= '.number_format($target).')', $ok ? 'PASS' : 'FAIL'],
            ]
        );

        @unlink($sqlitePath);

        if (! $ok) {
            $this->error('Stress test FAILED.');

            return self::FAILURE;
        }

        $this->info('Stress test PASSED: desktop sync can pull 200,000+ rows with resume.');

        return self::SUCCESS;
    }
}
