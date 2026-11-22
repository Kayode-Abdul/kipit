<?php

namespace App\Console\Commands;

use App\Helpers\DesktopHelper;
use App\Services\SyncDriftRepairService;
use Illuminate\Console\Command;

class RepairDesktopFromCloud extends Command
{
    protected $signature = 'desktop:repair-from-cloud
                            {--force : Repair even if the current integrity check passes}';

    protected $description = 'Mandatory cloud-wins repair when local desktop SQLite was altered or drifted';

    public function handle(SyncDriftRepairService $repair): int
    {
        if (! DesktopHelper::isDesktopApp() && ! app()->environment('testing')) {
            // Allow explicit desktop env for packaged app; testing may set NATIVEPHP_RUNNING.
            if (! getenv('NATIVEPHP_RUNNING') && ! env('NATIVEPHP_RUNNING')) {
                $this->warn('Not running as desktop app.');
            }
        }

        $result = $repair->repairFromCloudIfNeeded((bool) $this->option('force'));
        if ($result['repaired']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->warn($result['message']);

        return str_contains(strtolower($result['message']), 'failed')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
