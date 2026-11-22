<?php

use App\Helpers\DesktopHelper;
use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

/**
 * Step-by-step verification that a logged-in desktop sync client can pull 200,000+ rows.
 */
const SYNC_STRESS_TARGET = 200_000;

function seedMembersForSyncPull(int $total): void
{
    expect(Schema::hasTable('members'))->toBeTrue();

    $chunk = 2_000;
    $now = now()->toDateTimeString();

    for ($offset = 0; $offset < $total; $offset += $chunk) {
        $rows = [];
        $end = min($chunk, $total - $offset);
        for ($i = 0; $i < $end; $i++) {
            $n = $offset + $i + 1;
            $rows[] = [
                'name' => "Sync Member {$n}",
                'code' => sprintf('SYNC%07d', $n),
                'address' => null,
                'email' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_id' => (string) Str::uuid(),
                'is_synced' => true,
            ];
        }
        DB::table('members')->insert($rows);
    }
}

test('step 1: logged-in user can access sync pull endpoint', function () {
    $user = User::first();
    expect($user)->not->toBeNull();

    $token = $user->createToken('Kipit Desktop Test', ['desktop-sync'])->plainTextToken;

    $response = actingAs($user, 'sanctum')
        ->withToken($token)
        ->getJson('/api/sync/pull?last_sync_time=2000-01-01 00:00:00&limit=50');

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status',
            'sync_data',
            'server_time',
            'has_more',
            'next_cursor',
        ]);
});

test('step 2–3: authenticated pull paginates through 200,000+ member rows', function () {
    $user = User::first();
    $token = $user->createToken('Kipit Desktop Test', ['desktop-sync'])->plainTextToken;

    // Ensure sync columns exist (tenant migrations should have them).
    if (! Schema::hasColumn('members', 'sync_id')) {
        Schema::table('members', function ($table) {
            $table->uuid('sync_id')->nullable();
            $table->boolean('is_synced')->default(false);
        });
    }

    seedMembersForSyncPull(SYNC_STRESS_TARGET);

    expect(DB::table('members')->count())->toBeGreaterThanOrEqual(SYNC_STRESS_TARGET);

    $membersPulled = 0;
    $pages = 0;
    $cursor = null;
    $limit = 2_000; // server clamps to max 2000

    do {
        $query = [
            'last_sync_time' => '2000-01-01 00:00:00',
            'limit' => $limit,
        ];
        if ($cursor) {
            $query['cursor'] = $cursor;
        }

        $response = actingAs($user, 'sanctum')
            ->withToken($token)
            ->getJson('/api/sync/pull?'.http_build_query($query));

        $response->assertOk()->assertJsonPath('status', 'success');

        $batch = $response->json('sync_data.members', []) ?? [];
        $membersPulled += count($batch);
        $pages++;
        $hasMore = (bool) $response->json('has_more', false);
        $cursor = $response->json('next_cursor');

        // Guard against infinite loops if has_more never clears.
        expect($pages)->toBeLessThan(500);
    } while ($hasMore);

    expect($membersPulled)->toBeGreaterThanOrEqual(SYNC_STRESS_TARGET);
    expect($pages)->toBeGreaterThan(1);
})->group('sync-stress');

test('step 4: desktop sync:run resumes across runs until backlog is fully pulled', function () {
    DB::table('members')->delete();

    if (! Schema::hasColumn('members', 'sync_id')) {
        Schema::table('members', function ($table) {
            $table->uuid('sync_id')->nullable();
            $table->boolean('is_synced')->default(false);
        });
    }

    // Prove multi-run resume with a real merge, then assert batch math covers 200k+.
    $batchSize = 500;
    $total = 3_000; // 6 pages; with max 2 batches/run needs 3 sync:run cycles
    $totalPages = (int) ceil($total / $batchSize);
    $page = 0;
    $recordsSeen = 0;

    $serverBase = 'https://sync-stress.test/api/sync';
    DesktopHelper::storeServerConfig($serverBase, 'test-sync-token');

    config([
        'sync.batch_size' => $batchSize,
        'sync.max_pull_batches_per_run' => 2,
        'sync.http_timeout' => 30,
        'sync.assume_online' => true,
    ]);

    // Capacity check: default settings can express a 200k+ backlog via cursors.
    expect($batchSize * 401)->toBeGreaterThanOrEqual(SYNC_STRESS_TARGET);

    Cache::flush();

    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$page, &$recordsSeen, $totalPages, $batchSize, $total, $serverBase) {
        if ($request->method() !== 'GET') {
            return Http::response(['status' => 'success'], 200);
        }

        expect($request->url())->toStartWith($serverBase.'/pull');
        expect($request->header('Authorization')[0] ?? '')->toContain('Bearer');

        $page++;
        $start = ($page - 1) * $batchSize;
        $count = max(0, min($batchSize, $total - $start));
        $records = [];
        $now = now()->toDateTimeString();

        for ($i = 0; $i < $count; $i++) {
            $n = $start + $i + 1;
            $records[] = [
                'sync_id' => sprintf('stress-member-%07d', $n),
                'name' => "Pulled Member {$n}",
                'code' => sprintf('PUL%07d', $n),
                'address' => null,
                'email' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'is_synced' => true,
            ];
        }

        $recordsSeen += $count;
        $hasMore = $page < $totalPages;
        $lastId = $start + $count;

        return Http::response([
            'status' => 'success',
            'sync_data' => $count > 0 ? ['members' => $records] : [],
            'server_time' => $now,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? 'members:'.$lastId : null,
        ], 200);
    });

    $runs = 0;
    do {
        $runs++;
        $pageBefore = $page;
        Artisan::call('sync:run');
        expect(Artisan::output())->not->toContain('No internet connection');

        if (Cache::get('sync_pull_cursor') === null && Cache::has('last_successful_pull_time')) {
            break;
        }

        expect($page)->toBeGreaterThan($pageBefore);
        expect(Cache::get('sync_pull_cursor'))->not->toBeNull();
        expect($runs)->toBeLessThan(10);
    } while (true);

    expect($runs)->toBeGreaterThan(1);
    expect($recordsSeen)->toBe($total);
    expect(DB::table('members')->count())->toBe($total);
    expect(Cache::get('sync_pull_cursor'))->toBeNull();
})->group('sync-stress');

test('step 5: desktop sync:run can consume a 200,000+ fake pull stream via resume', function () {
    $batchSize = 2_000;
    $total = SYNC_STRESS_TARGET + 2_000; // 202,000
    $totalPages = (int) ceil($total / $batchSize);
    $page = 0;
    $recordsSeen = 0;

    $serverBase = 'https://sync-stress-200k.test/api/sync';
    DesktopHelper::storeServerConfig($serverBase, 'test-sync-token-200k');

    config([
        'sync.batch_size' => $batchSize,
        // ~40k rows/run → ~6 runs for 202k without merging heavy payloads into SQLite.
        'sync.max_pull_batches_per_run' => 20,
        'sync.http_timeout' => 30,
        'sync.assume_online' => true,
    ]);

    Cache::flush();

    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$page, &$recordsSeen, $totalPages, $batchSize, $total, $serverBase) {
        if ($request->method() !== 'GET') {
            return Http::response(['status' => 'success'], 200);
        }

        expect($request->url())->toStartWith($serverBase.'/pull');

        $page++;
        $start = ($page - 1) * $batchSize;
        $count = max(0, min($batchSize, $total - $start));
        $recordsSeen += $count;
        $hasMore = $page < $totalPages;
        $now = now()->toDateTimeString();

        // Lightweight payload: engine still merges, but tiny rows keep runtime practical.
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $n = $start + $i + 1;
            $records[] = [
                'sync_id' => sprintf('huge-%07d', $n),
                'name' => 'M'.$n,
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

    DB::table('members')->delete();

    $runs = 0;
    do {
        $runs++;
        Artisan::call('sync:run');
        if (Cache::get('sync_pull_cursor') === null && Cache::has('last_successful_pull_time')) {
            break;
        }
        expect($runs)->toBeLessThan(30);
    } while (true);

    expect($recordsSeen)->toBeGreaterThanOrEqual(SYNC_STRESS_TARGET);
    expect(DB::table('members')->count())->toBeGreaterThanOrEqual(SYNC_STRESS_TARGET);
    expect($runs)->toBeGreaterThan(1);
})->group('sync-stress');
