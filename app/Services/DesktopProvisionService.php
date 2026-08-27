<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use App\Services\DatabaseIntegrityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
            $response = Http::timeout(15)->post("{$serverUrl}/api/auth/desktop-authenticate", [
                'email' => $email,
                'password' => $password,
                'device_name' => 'Kipit Desktop ' . PHP_OS_FAMILY,
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
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download the full tenant dataset from the live server.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function downloadTenantData(string $serverUrl, string $token): array
    {
        $serverUrl = rtrim($serverUrl, '/');

        try {
            $response = Http::timeout(120)
                ->withToken($token)
                ->get("{$serverUrl}/api/desktop/provision");

            if ($response->successful() && $response->json('success')) {
                return [
                    'success' => true,
                    'message' => 'Data downloaded successfully.',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Failed to download data from server.'),
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
                'message' => 'Failed to download data: ' . $e->getMessage(),
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
        $tables = $tenantData['tables'] ?? [];
        $counts = [];

        // Define insertion order to respect foreign key constraints
        $insertionOrder = [
            'roles',
            'permissions',
            'role_has_permissions',
            'users',
            'model_has_roles',
            'model_has_permissions',
            'abouts',
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
            // Process tables in the correct order
            foreach ($insertionOrder as $tableName) {
                if (!isset($tables[$tableName]) || empty($tables[$tableName])) {
                    continue;
                }

                if (!Schema::hasTable($tableName)) {
                    Log::warning("Desktop seed: table {$tableName} does not exist locally, skipping.");
                    continue;
                }

                $records = $tables[$tableName];
                $inserted = 0;

                foreach ($records as $record) {
                    $recordData = (array) $record;

                    // Mark as synced since this came from the server
                    if (Schema::hasColumn($tableName, 'is_synced')) {
                        $recordData['is_synced'] = true;
                    }

                    try {
                        // Check for existing records by sync_id or id
                        $exists = false;
                        if (!empty($recordData['sync_id'])) {
                            $exists = DB::table($tableName)->where('sync_id', $recordData['sync_id'])->exists();
                        } elseif (!empty($recordData['id'])) {
                            $exists = DB::table($tableName)->where('id', $recordData['id'])->exists();
                        }

                        if (!$exists) {
                            DB::table($tableName)->insert($recordData);
                            $inserted++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Desktop seed: failed to insert record in {$tableName}: {$e->getMessage()}");
                    }
                }

                $counts[$tableName] = $inserted;
            }

            // Also process any tables not in the ordered list
            foreach ($tables as $tableName => $records) {
                if (in_array($tableName, $insertionOrder) || empty($records)) {
                    continue;
                }

                if (!Schema::hasTable($tableName)) {
                    continue;
                }

                $inserted = 0;
                foreach ($records as $record) {
                    $recordData = (array) $record;

                    if (Schema::hasColumn($tableName, 'is_synced')) {
                        $recordData['is_synced'] = true;
                    }

                    try {
                        DB::table($tableName)->insert($recordData);
                        $inserted++;
                    } catch (\Throwable $e) {
                        Log::warning("Desktop seed: extra table {$tableName}: {$e->getMessage()}");
                    }
                }

                $counts[$tableName] = $inserted;
            }

            DB::commit();

            // Refresh the integrity checksum after legitimate data seeding
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
                'message' => 'Failed to seed local database: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store server configuration for future sync operations.
     */
    public function storeServerConfig(string $serverUrl, string $token): void
    {
        DesktopHelper::storeServerConfig($serverUrl, $token);
    }
}
