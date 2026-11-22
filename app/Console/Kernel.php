<?php

namespace App\Console;

use App\Console\Commands\DeleteTempFile;
use App\Console\Commands\FCM;
use App\Jobs\RunSyncEngineJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DeleteTempFile::class)->daily();
        $schedule->command(FCM::class)->daily();

        // Queue when QUEUE_CONNECTION is not sync; otherwise runs inline via the job driver.
        $schedule->job(new RunSyncEngineJob)
            ->everyMinute()
            ->withoutOverlapping(15);
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
