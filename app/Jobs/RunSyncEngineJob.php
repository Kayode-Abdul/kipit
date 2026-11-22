<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunSyncEngineJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Allow large multi-batch pulls (200k+ resumes across scheduled runs). */
    public int $timeout = 900;

    public int $tries = 1;

    public int $uniqueFor = 600;

    public function handle(): void
    {
        Artisan::call('sync:run');
    }
}
