<?php

namespace App\Console\Commands;

use App\Helpers\DesktopHelper;
use App\Services\DatabaseIntegrityService;
use Illuminate\Console\Command;

class VerifyDatabaseIntegrity extends Command
{
    protected $signature = 'desktop:verify-integrity
                            {--refresh : Regenerate the checksum without verifying}';

    protected $description = 'Verify the integrity of the local SQLite database against stored checksums';

    public function handle(DatabaseIntegrityService $service): int
    {
        if (!DesktopHelper::isDesktopApp()) {
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

        return self::FAILURE;
    }
}
