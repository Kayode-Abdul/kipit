<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunSyncEngine extends Command
{
    protected $signature = 'sync:run';
    protected $description = 'Runs the offline-to-online background synchronization engine.';
    protected $liveServerUrl;

    public function __construct()
    {
        parent::__construct();
        // Uses the Desktop config if available, otherwise falls back to .env
        $this->liveServerUrl = \App\Helpers\DesktopHelper::getServerUrl() ?? env('SYNC_LIVE_SERVER_URL', 'https://kipitpos.com/api/sync');
    }

    protected $tables = [
        'users', 'categories', 'products', 'members', 'sellings', 'selling_details',
        'stocks', 'suppliers', 'purchasings', 'stock_opnames', 'stock_opname_items',
        'receivables', 'receivable_items', 'receivable_payments', 'cash_drawers',
        'cart_items', 'abouts', 'payment_methods', 'roles', 'permissions', 'vouchers',
        'model_has_roles', 'model_has_permissions', 'role_has_permissions'
    ];

    public function handle()
    {
        $this->info("Starting Offline Sync Engine...");
        
        // Ensure authentication token is configured in the desktop app
        $apiToken = \App\Helpers\DesktopHelper::getSyncToken() ?? env('SYNC_LIVE_SERVER_TOKEN', ''); 
        
        if (!$this->isOnline()) {
            $this->warn("No internet connection detected. Aborting sync.");
            return Command::SUCCESS;
        }

        $this->pushLocalData($apiToken);
        $this->pullRemoteData($apiToken);

        $this->info("Sync Cycle Complete.");
        return Command::SUCCESS;
    }

    protected function pushLocalData($apiToken)
    {
        $this->info("Gathering local records for Push...");
        $pushPayload = [];
        $recordsToMarkSynced = [];

        foreach ($this->tables as $tableName) {
            $unsyncedRecords = DB::table($tableName)->where('is_synced', false)->get();
            if ($unsyncedRecords->isNotEmpty()) {
                $pushPayload[$tableName] = $unsyncedRecords->toArray();
                $recordsToMarkSynced[$tableName] = $unsyncedRecords->pluck('sync_id')->toArray();
            }
        }

        if (empty($pushPayload)) {
            $this->line("No local changes to push.");
            return;
        }

        try {
            $response = Http::timeout(30)->withToken($apiToken)->post("{$this->liveServerUrl}/push", [
                'sync_data' => $pushPayload
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $this->info("Push accepted by Live Server. Marking local records as synced.");
                foreach ($recordsToMarkSynced as $tableName => $syncIds) {
                    DB::table($tableName)->whereIn('sync_id', $syncIds)->update(['is_synced' => true]);
                }
            } else {
                $this->error("Live Server rejected push payload: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Failed to push data: " . $e->getMessage());
        }
    }

    protected function pullRemoteData($apiToken)
    {
        $this->info("Requesting Pull from Live Server...");
        $lastSyncTime = \Illuminate\Support\Facades\Cache::get('last_successful_pull_time', '2000-01-01 00:00:00');

        try {
            $response = Http::timeout(30)->withToken($apiToken)->get("{$this->liveServerUrl}/pull", [
                'last_sync_time' => $lastSyncTime
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $pullData = $response->json('sync_data', []);
                $serverTime = $response->json('server_time');

                if (empty($pullData)) {
                    $this->line("No new remote changes to pull.");
                } else {
                    $this->mergePulledData($pullData);
                }

                \Illuminate\Support\Facades\Cache::put('last_successful_pull_time', $serverTime);
                $this->info("Pull successful. Horizon timestamp updated to {$serverTime}.");
            } else {
                $this->error("Failed to pull data: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Failed to pull data from server: " . $e->getMessage());
        }
    }

    protected function mergePulledData(array $pullData)
    {
        DB::beginTransaction();
        try {
            foreach ($pullData as $tableName => $records) {
                foreach ($records as $recordData) {
                    if (empty($recordData['sync_id'])) {
                        continue;
                    }

                    $existingRecord = DB::table($tableName)->where('sync_id', $recordData['sync_id'])->first();
                    $recordData = (array)$recordData;
                    
                    // Do not overwrite local integers to avoid breaking local relational mappings
                    unset($recordData['id']); 
                    $recordData['is_synced'] = true; 

                    if (!$existingRecord) {
                        DB::table($tableName)->insert($recordData);
                    } else {
                        // Conflict resolution based on updated_at
                        if (isset($recordData['updated_at']) && isset($existingRecord->updated_at)) {
                            if (strtotime($recordData['updated_at']) > strtotime($existingRecord->updated_at)) {
                                DB::table($tableName)->where('sync_id', $recordData['sync_id'])->update($recordData);
                            }
                        } else {
                            DB::table($tableName)->where('sync_id', $recordData['sync_id'])->update($recordData);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to merge pulled data: " . $e->getMessage());
        }
    }

    protected function isOnline()
    {
        $connected = @fsockopen("www.google.com", 80);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }
}
