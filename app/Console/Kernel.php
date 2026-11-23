<?php

namespace App\Console;

use App\Console\Commands\DeleteTempFile;
use App\Console\Commands\FCM;
use App\Helpers\DesktopHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DeleteTempFile::class)->daily();
        $schedule->command(FCM::class)->daily();

        // Shared-host friendly: run the command inline during schedule:run
        // (no Redis/Horizon worker required). Skip on central web cron unless enabled.
        $schedule->command('sync:run')
            ->everyMinute()
            ->withoutOverlapping(15)
            ->when(fn () => DesktopHelper::isDesktopApp() || (bool) config('sync.schedule_on_server', false));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
