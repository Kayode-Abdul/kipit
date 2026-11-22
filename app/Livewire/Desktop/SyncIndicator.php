<?php

namespace App\Livewire\Desktop;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class SyncIndicator extends Component
{
    public $isSyncing = false;
    public $lastSynced = null;

    public function checkSyncStatus()
    {
        $this->isSyncing = Cache::get('sync_engine_running', false);
        
        $lastRun = Cache::get('sync_last_run');
        if ($lastRun) {
            $this->lastSynced = \Carbon\Carbon::parse($lastRun)->diffForHumans();
        }
    }

    public function mount()
    {
        $this->checkSyncStatus();
    }

    public function render()
    {
        return view('livewire.desktop.sync-indicator');
    }
}
