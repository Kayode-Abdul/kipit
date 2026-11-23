<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RunSyncEngine extends Command
{
    protected $signature = 'sync:run';

    protected $description = 'Runs the offline-to-online background synchronization engine.';

    protected string $liveServerUrl;

    protected int $batchSize;

    protected int $httpTimeout;

    protected array $foreignKeyMapping = [
        'category_id' => 'categories',
        'product_id' => 'products',
        'member_id' => 'members',
        'selling_id' => 'sellings',
        'user_id' => 'users',
        'supplier_id' => 'suppliers',
        'purchasing_id' => 'purchasings',
        'stock_opname_id' => 'stock_opnames',
        'debt_id' => 'debts',
        'payment_method_id' => 'payment_methods',
        'cash_drawer_id' => 'cash_drawers',
        'table_id' => 'tables',
        'price_unit_id' => 'price_units',
        'subscription_plan_id' => 'subscription_plans',
        'role_id' => 'roles',
        'permission_id' => 'permissions',
    ];

    protected array $tables = [
        'users', 'categories', 'products', 'members', 'sellings', 'selling_details',
        'stocks', 'suppliers', 'purchasings', 'stock_opnames', 'stock_opname_items',
        'receivables', 'receivable_items', 'receivable_payments', 'cash_drawers',
        'cart_items', 'abouts', 'settings', 'features', 'profiles', 'payment_methods',
        'roles', 'permissions', 'vouchers', 'model_has_roles', 'model_has_permissions',
        'role_has_permissions',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->liveServerUrl = \App\Helpers\DesktopHelper::getServerUrl()
            ?? env('SYNC_LIVE_SERVER_URL', 'https://kipitpos.com/api/sync');
        $this->batchSize = max(50, (int) config('sync.batch_size', 500));
        $this->httpTimeout = max(15, (int) config('sync.http_timeout', 60));
    }

    public function handle()
    {
        $this->info('Starting Offline Sync Engine...');
        Cache::put('sync_engine_running', true, 900);

        try {
            $apiToken = \App\Helpers\DesktopHelper::getSyncToken() ?? env('SYNC_LIVE_SERVER_TOKEN', '');

            if (! $this->isOnline()) {
                $this->warn('No internet connection detected. Aborting sync.');

                return Command::SUCCESS;
            }

            $integrity = app(\App\Services\DatabaseIntegrityService::class);
            $preferCloud = $integrity->shouldPreferCloud();
            $verifyMinutes = max(5, (int) config('sync.integrity_verify_minutes', 60));
            $lastVerifiedAt = Cache::get('integrity_last_verified_at');
            $shouldVerify = $preferCloud
                || ! $lastVerifiedAt
                || now()->diffInMinutes($lastVerifiedAt) >= $verifyMinutes;

            if (! $preferCloud && $shouldVerify) {
                $check = $integrity->verify();
                Cache::put('integrity_last_verified_at', now(), 86400);
                if (! $check['valid']) {
                    $preferCloud = true;
                    $integrity->markCloudWinsRepair();
                    $this->error('Local integrity failed — skipping push and repairing from cloud (cloud wins).');
                    Cache::forget('sync_pull_cursor');
                    Cache::forget('sync_pull_pending_server_time');
                    Cache::put('last_successful_pull_time', '2000-01-01 00:00:00', 86400);
                    app(\App\Services\SyncDriftRepairService::class)->purgeBusinessTablesForRepair();
                }
            } elseif (! $preferCloud) {
                $this->line("Skipped integrity full scan (next due within {$verifyMinutes}m).");
            } else {
                $this->warn('Cloud-wins repair mode active — local push skipped.');
            }

            $pushedAny = false;
            if (! $preferCloud) {
                $pushedAny = $this->pushLocalData($apiToken);
            } else {
                $this->warn('Skipped local push to prevent tampered offline data from overwriting the cloud.');
            }

            $pulledAny = $this->pullRemoteData($apiToken, $preferCloud);

            // Role/permission rows may have changed; refresh Spatie cache for offline Gate checks.
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                // Only clear cloud-wins after a completed pull (no resume cursor left).
                $pullStillPending = (bool) Cache::get('sync_pull_cursor');
                if ($preferCloud && ! $pullStillPending) {
                    $integrity->refreshChecksum();
                    $integrity->clearCloudWinsRepair();
                    Cache::put('integrity_last_verified_at', now(), 86400);
                    $this->info('Integrity checksum refreshed after cloud-wins pull.');
                } elseif (! $preferCloud && ($pushedAny || $pulledAny)) {
                    // Refresh only when this cycle mutated data (avoids full fingerprint every minute).
                    $integrity->refreshChecksum();
                    Cache::put('integrity_last_verified_at', now(), 86400);
                } elseif ($preferCloud) {
                    $this->warn('Cloud-wins mode kept active until pull finishes (resume cursor pending).');
                }
            } catch (\Throwable $e) {
                Log::warning('Post-sync integrity refresh failed: '.$e->getMessage());
            }

            Cache::put('sync_last_metrics', [
                'at' => now()->toDateTimeString(),
                'pushed' => $pushedAny,
                'pulled' => $pulledAny,
                'prefer_cloud' => $preferCloud,
            ], 86400);

            $this->info('Sync Cycle Complete.');

            return Command::SUCCESS;
        } finally {
            Cache::forget('sync_engine_running');
            Cache::put('sync_last_run', now(), 86400);
        }
    }

    protected function pushLocalData(string $apiToken): bool
    {
        $this->info("Gathering local records for Push (batch size {$this->batchSize})...");
        $uploadDisk = config('filesystems.upload_disk', 'public');
        $pushedAny = false;

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'is_synced')) {
                continue;
            }

            $afterId = 0;

            do {
                $unsyncedRecords = DB::table($tableName)
                    ->where('is_synced', false)
                    ->where('id', '>', $afterId)
                    ->orderBy('id')
                    ->limit($this->batchSize)
                    ->get();

                if ($unsyncedRecords->isEmpty()) {
                    break;
                }

                $pushedAny = true;
                $afterId = (int) $unsyncedRecords->last()->id;

                // Batch-load FK → sync_id maps once per page (avoids N+1 on large pushes).
                $fkLookups = [];
                foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                    $ids = $unsyncedRecords->pluck($foreignKey)->filter()->unique()->values();
                    if ($ids->isEmpty() || ! Schema::hasTable($refTable)) {
                        $fkLookups[$foreignKey] = [];

                        continue;
                    }
                    $fkLookups[$foreignKey] = DB::table($refTable)
                        ->whereIn('id', $ids->all())
                        ->pluck('sync_id', 'id')
                        ->all();
                }

                $mappedRecords = $unsyncedRecords->map(function ($item) use ($tableName, $apiToken, $uploadDisk, $fkLookups) {
                    $record = (array) $item;

                    if ($tableName === 'products' && ! empty($record['hero_images'])) {
                        $this->pushProductImages($record, $apiToken, $uploadDisk);
                    }

                    foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                        if (array_key_exists($foreignKey, $record) && $record[$foreignKey] !== null) {
                            $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                            $record[$syncKey] = $fkLookups[$foreignKey][$record[$foreignKey]] ?? null;
                        }
                    }

                    return $record;
                })->toArray();

                $syncIds = $unsyncedRecords->pluck('sync_id')->filter()->values()->toArray();

                try {
                    $response = Http::timeout($this->httpTimeout)
                        ->withToken($apiToken)
                        ->post("{$this->liveServerUrl}/push", [
                            'sync_data' => [$tableName => $mappedRecords],
                        ]);

                    if ($response->successful() && $response->json('status') === 'success') {
                        if (! empty($syncIds)) {
                            DB::table($tableName)->whereIn('sync_id', $syncIds)->update(['is_synced' => true]);
                        }
                        $this->line("Pushed {$unsyncedRecords->count()} row(s) from {$tableName}.");
                    } else {
                        $this->error("Live Server rejected push for {$tableName}: ".$response->body());

                        return $pushedAny;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to push {$tableName}: ".$e->getMessage());

                    return $pushedAny;
                }
            } while ($unsyncedRecords->count() === $this->batchSize);
        }

        if (! $pushedAny) {
            $this->line('No local changes to push.');
        }

        return $pushedAny;
    }

    protected function pushProductImages(array $record, string $apiToken, string $uploadDisk): void
    {
        $images = $this->parseHeroImages($record['hero_images'] ?? null);

        foreach ($images as $imagePath) {
            $imagePath = ltrim((string) $imagePath, '/');
            if ($imagePath === '' || ! Storage::disk($uploadDisk)->exists($imagePath)) {
                continue;
            }

            try {
                $absPath = Storage::disk($uploadDisk)->path($imagePath);
                $fileContent = \App\Services\FileEncryptionService::getDecryptedContents($absPath);
                Http::timeout($this->httpTimeout)->withToken($apiToken)
                    ->attach('file', $fileContent, basename($imagePath))
                    ->post("{$this->liveServerUrl}/push/media", [
                        'path' => $imagePath,
                    ]);
            } catch (\Exception $e) {
                $this->error("Failed to push image {$imagePath}: ".$e->getMessage());
            }
        }
    }

    protected function pullRemoteData(string $apiToken, bool $forceCloudWins = false): bool
    {
        $this->info('Requesting Pull from Live Server...');
        $lastSyncTime = Cache::get('last_successful_pull_time', '2000-01-01 00:00:00');
        $cursor = Cache::get('sync_pull_cursor');
        $pendingServerTime = Cache::get('sync_pull_pending_server_time', $lastSyncTime);
        $latestServerTime = $pendingServerTime;
        $pulledAny = false;
        $batches = 0;
        $maxBatches = max(0, (int) config('sync.max_pull_batches_per_run', 100));

        if ($cursor) {
            $this->line("Resuming pull from cursor {$cursor} (since {$lastSyncTime}).");
        }
        if ($forceCloudWins) {
            $this->warn('Cloud-wins merge enabled (local drift/tamper repair).');
        }

        try {
            do {
                $query = [
                    'last_sync_time' => $lastSyncTime,
                    'limit' => $this->batchSize,
                ];

                if ($cursor) {
                    $query['cursor'] = $cursor;
                }

                $response = Http::timeout($this->httpTimeout)
                    ->withToken($apiToken)
                    ->get("{$this->liveServerUrl}/pull", $query);

                if (! ($response->successful() && $response->json('status') === 'success')) {
                    $this->error('Failed to pull data: '.$response->body());

                    return $pulledAny;
                }

                $pullData = $response->json('sync_data', []);
                $serverTime = $response->json('server_time');
                $hasMore = (bool) $response->json('has_more', false);
                $cursor = $response->json('next_cursor');
                $batches++;

                if ($serverTime) {
                    $latestServerTime = $serverTime;
                    Cache::put('sync_pull_pending_server_time', $latestServerTime, 86400);
                }

                if (! empty($pullData)) {
                    $pulledAny = true;
                    $rowCount = collect($pullData)->sum(fn ($rows) => is_countable($rows) ? count($rows) : 0);
                    $this->line("Pulled batch #{$batches} ({$rowCount} row(s)).");
                    $this->mergePulledData($pullData, $forceCloudWins);
                }

                if ($hasMore && $cursor) {
                    // Persist cursor so a 200k+ backlog can resume on the next sync:run.
                    Cache::put('sync_pull_cursor', $cursor, 86400);
                }

                if (! $hasMore) {
                    Cache::forget('sync_pull_cursor');
                    Cache::forget('sync_pull_pending_server_time');
                    Cache::put('last_successful_pull_time', $latestServerTime, 86400);
                    $this->info($pulledAny
                        ? "Pull complete. Horizon timestamp updated to {$latestServerTime}."
                        : 'No new remote changes to pull.');

                    return $pulledAny;
                }

                if ($maxBatches > 0 && $batches >= $maxBatches) {
                    $this->warn("Reached max {$maxBatches} pull batches this run; will resume from cursor on next sync.");

                    return $pulledAny;
                }
            } while (true);
        } catch (\Exception $e) {
            $this->error('Failed to pull data from server: '.$e->getMessage());
        }

        return $pulledAny;
    }

    protected function mergePulledData(array $pullData, bool $forceCloudWins = false): void
    {
        $uploadDisk = config('filesystems.upload_disk', 'public');
        $pendingProductImages = [];

        foreach ($pullData as $tableName => $records) {
            $normalized = [];
            foreach ($records as $recordData) {
                $normalized[] = (array) $recordData;
            }

            // Batch-load sync_id → local id maps once per pulled table page.
            $fkLookups = [];
            foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                $syncIds = collect($normalized)->pluck($syncKey)->filter()->unique()->values();
                if ($syncIds->isEmpty() || ! Schema::hasTable($refTable)) {
                    $fkLookups[$syncKey] = [];

                    continue;
                }
                $fkLookups[$syncKey] = DB::table($refTable)
                    ->whereIn('sync_id', $syncIds->all())
                    ->pluck('id', 'sync_id')
                    ->all();
            }

            $existingBySyncId = [];
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'sync_id')) {
                $pageSyncIds = collect($normalized)->pluck('sync_id')->filter()->unique()->values();
                if ($pageSyncIds->isNotEmpty()) {
                    $existingBySyncId = DB::table($tableName)
                        ->whereIn('sync_id', $pageSyncIds->all())
                        ->get()
                        ->keyBy('sync_id')
                        ->all();
                }
            }

            // Per-table transaction keeps locks short on shared hosts / SQLite.
            DB::beginTransaction();
            try {
                foreach ($normalized as $recordData) {
                    if ($tableName === 'settings' && ! empty($recordData['key'])) {
                        \App\Models\Tenants\Setting::set($recordData['key'], $recordData['value'] ?? null);

                        continue;
                    }

                    if ($tableName === 'features' && ! empty($recordData['name'])) {
                        DB::table('features')->updateOrInsert(
                            [
                                'name' => $recordData['name'],
                                'scope' => $recordData['scope'] ?? '',
                            ],
                            [
                                'value' => $recordData['value'] ?? json_encode(false),
                                'updated_at' => $recordData['updated_at'] ?? now(),
                                'created_at' => $recordData['created_at'] ?? now(),
                            ]
                        );

                        continue;
                    }

                    if ($tableName === 'abouts') {
                        unset($recordData['id']);
                        $existingAbout = DB::table('abouts')->first();
                        if ($existingAbout) {
                            DB::table('abouts')->where('id', $existingAbout->id)->update($recordData);
                        } else {
                            DB::table('abouts')->insert($recordData);
                        }

                        continue;
                    }

                    if ($tableName === 'profiles' && ! empty($recordData['user_id'])) {
                        if (! empty($recordData['user_sync_id'])) {
                            $recordData['user_id'] = $fkLookups['user_sync_id'][$recordData['user_sync_id']]
                                ?? (DB::table('users')->where('sync_id', $recordData['user_sync_id'])->value('id')
                                    ?: $recordData['user_id']);
                            unset($recordData['user_sync_id']);
                        }

                        unset($recordData['id']);
                        DB::table('profiles')->updateOrInsert(
                            ['user_id' => $recordData['user_id']],
                            $recordData
                        );

                        continue;
                    }

                    if (empty($recordData['sync_id'])) {
                        continue;
                    }

                    foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                        $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                        if (array_key_exists($syncKey, $recordData)) {
                            if ($recordData[$syncKey] !== null) {
                                $recordData[$foreignKey] = $fkLookups[$syncKey][$recordData[$syncKey]] ?? null;
                            } else {
                                $recordData[$foreignKey] = null;
                            }
                            unset($recordData[$syncKey]);
                        }
                    }

                    $existingRecord = $existingBySyncId[$recordData['sync_id']] ?? null;

                    unset($recordData['id']);
                    if (Schema::hasColumn($tableName, 'is_synced')) {
                        $recordData['is_synced'] = true;
                    }

                    if (! $existingRecord) {
                        DB::table($tableName)->insert($recordData);
                    } elseif ($forceCloudWins) {
                        // Integrity/drift repair: cloud always overwrites local.
                        DB::table($tableName)->where('sync_id', $recordData['sync_id'])->update($recordData);
                    } elseif (isset($recordData['updated_at'], $existingRecord->updated_at)) {
                        if (strtotime($recordData['updated_at']) > strtotime($existingRecord->updated_at)) {
                            DB::table($tableName)->where('sync_id', $recordData['sync_id'])->update($recordData);
                        }
                    } else {
                        DB::table($tableName)->where('sync_id', $recordData['sync_id'])->update($recordData);
                    }

                    if ($tableName === 'products' && ! empty($recordData['hero_images'])) {
                        $pendingProductImages[] = $recordData;
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to merge pulled {$tableName}: ".$e->getMessage());

                return;
            }
        }

        // Media HTTP outside DB transactions (shared-host / SQLite friendly).
        foreach ($pendingProductImages as $recordData) {
            $this->pullProductImages($recordData, $uploadDisk);
        }
    }

    protected function pullProductImages(array $recordData, string $uploadDisk): void
    {
        $images = $this->parseHeroImages($recordData['hero_images'] ?? null);
        $baseUrl = preg_replace('#/api/sync$#', '', rtrim($this->liveServerUrl, '/'));

        foreach ($images as $imagePath) {
            $imagePath = ltrim((string) $imagePath, '/');
            if ($imagePath === '' || str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                continue;
            }

            if (Storage::disk($uploadDisk)->exists($imagePath)) {
                continue;
            }

            try {
                $imageResponse = Http::timeout($this->httpTimeout)->get("{$baseUrl}/storage/{$imagePath}");
                if ($imageResponse->successful()) {
                    Storage::disk($uploadDisk)->put($imagePath, $imageResponse->body());
                    $absPath = Storage::disk($uploadDisk)->path($imagePath);
                    \App\Services\FileEncryptionService::encryptAndStore($absPath);
                }
            } catch (\Exception $e) {
                $this->error("Failed to pull image {$imagePath}: ".$e->getMessage());
            }
        }
    }

    /**
     * hero_images may be comma-separated paths, JSON array, or already an array.
     *
     * @return array<int, string>
     */
    protected function parseHeroImages(mixed $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        if (! is_string($raw)) {
            return [];
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
    }

    protected function isOnline(): bool
    {
        if (app()->environment('testing') || (bool) config('sync.assume_online', false)) {
            return true;
        }

        $connected = @fsockopen('www.google.com', 80);
        if ($connected) {
            fclose($connected);

            return true;
        }

        return false;
    }
}
