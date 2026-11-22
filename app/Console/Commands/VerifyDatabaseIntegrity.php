<?php

namespace App\Console\Commands;

use App\Helpers\DesktopHelper;
use App\Services\DatabaseIntegrityService;
use App\Services\SyncDriftRepairService;
use Illuminate\Console\Command;

class VerifyDatabaseIntegrity extends Command
{
    protected $signature = 'desktop:verify-integrity
                            {--refresh : Regenerate the checksum without verifying}
                            {--no-repair : Do not auto-repair from cloud on failure}';

    protected $description = 'Verify local SQLite integrity; on failure, mandatory cloud-wins repair when online';

    public function handle(DatabaseIntegrityService $service, SyncDriftRepairService $repair): int
    {
        if (! DesktopHelper::isDesktopApp()) {
            $this->info('Not running in desktop mode — skipping integrity check.');

            return self::SUCCESS;
        }

        if ($this->option('refresh')) {
            $checksum = $service->refreshChecksum();
            $this->info("Checksum refreshed: {$checksum}");

            return self::SUCCESS;
        }

        $result = $service->verify();

        if ($result['valid']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        if ($this->option('no-repair')) {
            return self::FAILURE;
        }

        $this->warn('Starting mandatory cloud-wins repair…');
        $repairResult = $repair->repairFromCloudIfNeeded(force: true);
        $this->line($repairResult['message']);

        $recheck = $service->verify();
        if ($recheck['valid']) {
            $this->info('Integrity restored after cloud repair.');

            return self::SUCCESS;
        }

        $this->error('Integrity still failing after repair attempt. Check network/sync login.');

        return self::FAILURE;
    }
}
