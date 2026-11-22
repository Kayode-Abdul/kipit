<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * When local SQLite is tampered with or drifts from cloud, cloud wins.
 * Skips pushing local changes and forces a full pull with overwrite.
 */
class SyncDriftRepairService
{
    public function __construct(
        protected DatabaseIntegrityService $integrity
    ) {}

    /**
     * @return array{repaired: bool, message: string}
     */
    public function repairFromCloudIfNeeded(bool $force = false): array
    {
        if (! DesktopHelper::isDesktopApp()) {
            return ['repaired' => false, 'message' => 'Not a desktop app.'];
        }

        $verify = $this->integrity->verify();
        if ($verify['valid'] && ! $force) {
            return ['repaired' => false, 'message' => $verify['message']];
        }

        if (! DesktopHelper::getSyncToken() || ! DesktopHelper::getServerUrl()) {
            $this->integrity->markCloudWinsRepair();

            return [
                'repaired' => false,
                'message' => 'Integrity failed and no sync credentials are available. Connect this desktop to the cloud shop to repair.',
            ];
        }

        Log::warning('Starting mandatory cloud-wins drift repair.', [
            'reason' => $verify['message'] ?? 'forced',
        ]);

        $this->integrity->markCloudWinsRepair();

        // Force a full pull window; do not trust local timestamps.
        Cache::forget('sync_pull_cursor');
        Cache::forget('sync_pull_pending_server_time');
        Cache::put('last_successful_pull_time', '2000-01-01 00:00:00', 86400);

        // Remove local business rows that could win LWW over cloud after tampering.
        $this->purgeBusinessTablesForRepair();

        Artisan::call('sync:run');
        $output = Artisan::output();

        // sync:run refreshes checksum when cloud-wins pull completes (no resume cursor).
        if ($this->integrity->verify()['valid'] && ! Cache::get('sync_pull_cursor')) {
            $this->integrity->clearCloudWinsRepair();
        }

        return [
            'repaired' => true,
            'message' => 'Cloud repair completed. Local tampered rows were replaced from the server. '.$output,
        ];
    }

    /**
     * Wipe commercial tables so cloud rows cannot lose to tampered local timestamps.
     */
    public function purgeBusinessTablesForRepair(): void
    {
        // Keep users/roles so the cashier can still sign in locally during repair;
        // commercial tables are rebuilt from cloud.
        $purge = [
            'products', 'stocks', 'categories', 'members',
            'sellings', 'selling_details', 'purchasings',
            'stock_opnames', 'stock_opname_items',
            'receivables', 'receivable_items', 'receivable_payments',
            'cash_drawers', 'cart_items', 'vouchers', 'suppliers',
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($purge as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
