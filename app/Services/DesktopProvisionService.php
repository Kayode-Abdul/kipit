<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use App\Models\Tenants\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesktopProvisionService
{
    /**
     * Authenticate with the live server and get a sync token.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function authenticateWithServer(string $serverUrl, string $email, string $password): array
    {
        $serverUrl = rtrim($serverUrl, '/');

        try {
            $host = gethostname() ?: 'unknown-host';
            $response = Http::timeout(15)->post("{$serverUrl}/api/auth/desktop-authenticate", [
                'email' => $email,
                'password' => $password,
                'device_name' => 'Kipit Desktop '.PHP_OS_FAMILY.' · '.$host,
            ]);

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'message' => $response->json('message', 'Authentication successful.'),
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Authentication failed. Please check your credentials.'),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Could not connect to the server. Please check the URL and your internet connection.',
            ];
        } catch (\Throwable $e) {
            Log::error("Desktop auth error: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'An unexpected error occurred: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Download tenant data in chunks and seed locally as each chunk arrives.
     *
     * @return array{success: bool, message: string, data?: array, counts?: array}
     */
    public function downloadTenantData(string $serverUrl, string $token): array
    {
        $serverUrl = rtrim($serverUrl, '/');
        $timeout = max(30, (int) config('sync.http_timeout', 60));
        $batchSize = max(50, (int) config('sync.batch_size', 500));

        try {
            $manifestResponse = Http::timeout($timeout)
                ->withToken($token)
                ->get("{$serverUrl}/api/desktop/provision", [
                    'paginated' => 1,
                    'limit' => $batchSize,
                ]);

            if (! ($manifestResponse->successful() && $manifestResponse->json('success'))) {
                return [
                    'success' => false,
                    'message' => $manifestResponse->json('message', 'Failed to download provision manifest.'),
                ];
            }

            $manifest = $manifestResponse->json('data', []);
            $mode = $manifest['mode'] ?? 'full';

            // Older servers may still return a full payload
            if ($mode === 'full') {
                return [
                    'success' => true,
                    'message' => 'Data downloaded successfully.',
                    'data' => $manifest,
                ];
            }

            $tables = $manifest['tables'] ?? [];
            $features = $manifest['features'] ?? [];
            $settings = $manifest['settings'] ?? [];
            $counts = [];

            foreach ($tables as $tableName) {
                $afterId = 0;
                $inserted = 0;

                do {
                    $chunkResponse = Http::timeout($timeout)
                        ->withToken($token)
                        ->get("{$serverUrl}/api/desktop/provision", [
                            'paginated' => 1,
                            'table' => $tableName,
                            'after_id' => $afterId,
                            'limit' => $batchSize,
                        ]);

                    if (! ($chunkResponse->successful() && $chunkResponse->json('success'))) {
                        return [
                            'success' => false,
                            'message' => $chunkResponse->json('message', "Failed to download table {$tableName}."),
                        ];
                    }

                    $chunk = $chunkResponse->json('data', []);
                    $records = $chunk['records'] ?? [];
                    $hasMore = (bool) ($chunk['has_more'] ?? false);
                    $afterId = (int) ($chunk['next_after_id'] ?? $afterId);

                    if (! empty($records)) {
                        $seed = $this->seedTableChunk($tableName, $records);
                        $inserted += $seed;

                        if ($tableName === 'products') {
                            $this->downloadProductImages($serverUrl, $records, $timeout);
                        }
                    }
                } while ($hasMore);

                $counts[$tableName] = $inserted;
            }

            // Ensure shop settings (currency, tax, etc.) and feature flags are applied
            // even when older servers only returned them as side-channel maps.
            $this->seedSettingsMap($settings);
            $this->seedFeaturesMap($features);

            return [
                'success' => true,
                'message' => 'Data downloaded and seeded successfully.',
                'data' => [
                    'tables' => [],
                    'features' => $features,
                    'settings' => $settings,
                    'summary' => $counts,
                    'seeded_inline' => true,
                ],
                'counts' => $counts,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Connection lost during data download. Please try again.',
            ];
        } catch (\Throwable $e) {
            Log::error("Desktop provision download error: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'Failed to download data: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Seed the local SQLite database with downloaded tenant data.
     *
     * @return array{success: bool, message: string, counts?: array}
     */
    public function seedLocalDatabase(array $tenantData): array
    {
        if (! empty($tenantData['seeded_inline'])) {
            $this->seedSettingsMap($tenantData['settings'] ?? []);
            $this->seedFeaturesMap($tenantData['features'] ?? []);

            try {
                app(DatabaseIntegrityService::class)->refreshChecksum();
            } catch (\Throwable $checksumError) {
                Log::warning("Failed to refresh integrity checksum after seeding: {$checksumError->getMessage()}");
            }

            return [
                'success' => true,
                'message' => 'Local database seeded successfully.',
                'counts' => $tenantData['summary'] ?? [],
            ];
        }

        $tables = $tenantData['tables'] ?? [];
        $counts = [];

        $insertionOrder = [
            'roles',
            'permissions',
            'role_has_permissions',
            'users',
            'model_has_roles',
            'model_has_permissions',
            'abouts',
            'settings',
            'features',
            'profiles',
            'categories',
            'suppliers',
            'members',
            'products',
            'stocks',
            'payment_methods',
            'vouchers',
            'sellings',
            'selling_details',
            'purchasings',
            'stock_opnames',
            'stock_opname_items',
            'receivables',
            'receivable_items',
            'receivable_payments',
            'cash_drawers',
            'cart_items',
        ];

        DB::beginTransaction();
        try {
            foreach ($insertionOrder as $tableName) {
                if (! isset($tables[$tableName]) || empty($tables[$tableName])) {
                    continue;
                }

                if (! Schema::hasTable($tableName)) {
                    Log::warning("Desktop seed: table {$tableName} does not exist locally, skipping.");
                    continue;
                }

                $counts[$tableName] = $this->seedTableChunk($tableName, $tables[$tableName]);
            }

            foreach ($tables as $tableName => $records) {
                if (in_array($tableName, $insertionOrder, true) || empty($records)) {
                    continue;
                }

                if (! Schema::hasTable($tableName)) {
                    continue;
                }

                $counts[$tableName] = $this->seedTableChunk($tableName, $records);
            }

            $this->seedSettingsMap($tenantData['settings'] ?? []);
            $this->seedFeaturesMap($tenantData['features'] ?? []);

            DB::commit();

            try {
                app(DatabaseIntegrityService::class)->refreshChecksum();
            } catch (\Throwable $checksumError) {
                Log::warning("Failed to refresh integrity checksum after seeding: {$checksumError->getMessage()}");
            }

            return [
                'success' => true,
                'message' => 'Local database seeded successfully.',
                'counts' => $counts,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Desktop seed error: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'Failed to seed local database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Insert a chunk of rows for one table. Returns inserted count.
     */
    protected function seedTableChunk(string $tableName, array $records): int
    {
        if (! Schema::hasTable($tableName) || empty($records)) {
            return 0;
        }

        $inserted = 0;
        $hasSync = Schema::hasColumn($tableName, 'is_synced');

        foreach ($records as $record) {
            $recordData = (array) $record;

            if ($hasSync) {
                $recordData['is_synced'] = true;
            }

            try {
                if ($tableName === 'settings' && ! empty($recordData['key'])) {
                    DB::table('settings')->updateOrInsert(
                        ['key' => $recordData['key']],
                        [
                            'value' => $recordData['value'] ?? null,
                            'updated_at' => $recordData['updated_at'] ?? now(),
                            'created_at' => $recordData['created_at'] ?? now(),
                        ]
                    );
                    $inserted++;

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
                    $inserted++;

                    continue;
                }

                $exists = false;
                if (! empty($recordData['sync_id'])) {
                    $exists = DB::table($tableName)->where('sync_id', $recordData['sync_id'])->exists();
                } elseif (! empty($recordData['id'])) {
                    $exists = DB::table($tableName)->where('id', $recordData['id'])->exists();
                }

                if (! $exists) {
                    DB::table($tableName)->insert($recordData);
                    $inserted++;
                }
            } catch (\Throwable $e) {
                Log::warning("Desktop seed: failed to insert record in {$tableName}: {$e->getMessage()}");
            }
        }

        return $inserted;
    }

    public function storeServerConfig(string $serverUrl, string $token): void
    {
        DesktopHelper::storeServerConfig($serverUrl, $token);
    }

    /**
     * Apply shop settings (currency, tax, etc.) from a key => value map.
     */
    protected function seedSettingsMap(array $settings): void
    {
        if (empty($settings) || ! Schema::hasTable('settings')) {
            return;
        }

        foreach ($settings as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            try {
                Setting::set($key, $value);
            } catch (\Throwable $e) {
                Log::warning("Desktop seed: failed to set setting {$key}: {$e->getMessage()}");
            }
        }

        // Clear any stale cached defaults (e.g. IDR) from before settings were seeded.
        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Apply Pennant feature flags from a name => enabled map.
     */
    protected function seedFeaturesMap(array $features): void
    {
        if (empty($features) || ! Schema::hasTable('features')) {
            return;
        }

        foreach ($features as $name => $value) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            try {
                DB::table('features')->updateOrInsert(
                    ['name' => $name, 'scope' => ''],
                    [
                        'value' => json_encode((bool) $value),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning("Desktop seed: failed to set feature {$name}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Download product hero images into the local upload disk.
     */
    protected function downloadProductImages(string $serverUrl, array $records, int $timeout = 60): void
    {
        $uploadDisk = config('filesystems.upload_disk', 'public');
        $baseUrl = rtrim($serverUrl, '/');

        foreach ($records as $record) {
            $record = (array) $record;
            $images = $this->parseHeroImages($record['hero_images'] ?? null);

            foreach ($images as $imagePath) {
                $imagePath = ltrim((string) $imagePath, '/');
                if ($imagePath === '' || Str::startsWith($imagePath, ['http://', 'https://'])) {
                    continue;
                }

                try {
                    if (Storage::disk($uploadDisk)->exists($imagePath)) {
                        continue;
                    }

                    $response = Http::timeout($timeout)->get("{$baseUrl}/storage/{$imagePath}");
                    if (! $response->successful()) {
                        continue;
                    }

                    Storage::disk($uploadDisk)->put($imagePath, $response->body());
                    $absPath = Storage::disk($uploadDisk)->path($imagePath);
                    FileEncryptionService::encryptAndStore($absPath);
                } catch (\Throwable $e) {
                    Log::warning("Desktop provision: failed to download image {$imagePath}: {$e->getMessage()}");
                }
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

        if (Str::startsWith($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
    }
}
