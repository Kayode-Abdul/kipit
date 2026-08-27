<?php

namespace App\Livewire;

use App\Helpers\DesktopHelper;
use App\Services\DesktopProvisionService;
use Livewire\Component;

class DesktopSetup extends Component
{
    public int $step = 1;
    public string $serverUrl = '';
    public string $email = '';
    public string $password = '';
    
    public ?string $error = null;
    public ?array $tenantMeta = null;
    public ?string $syncToken = null;
    
    public int $progress = 0;
    public string $downloadStatus = 'Starting download...';
    public ?array $downloadCounts = null;

    public function mount()
    {
        if (DesktopHelper::isSetupComplete()) {
            return redirect()->route('desktop.login');
        }
    }

    public function connect(DesktopProvisionService $provisionService)
    {
        $this->validate([
            'serverUrl' => 'required|url',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $this->error = null;

        $response = $provisionService->authenticateWithServer(
            $this->serverUrl,
            $this->email,
            $this->password
        );

        if ($response['success']) {
            $this->tenantMeta = $response['data']['tenant'] ?? null;
            $this->syncToken = $response['data']['token'] ?? null;
            $this->step = 2;
            
            // Automatically start the download process
            $this->dispatch('start-download');
        } else {
            $this->error = $response['message'];
        }
    }

    #[\Livewire\Attributes\On('start-download')]
    public function downloadData(DesktopProvisionService $provisionService)
    {
        $this->error = null;
        $this->progress = 10;
        $this->downloadStatus = 'Downloading data from server...';

        $response = $provisionService->downloadTenantData(
            $this->serverUrl,
            $this->syncToken
        );

        if (!$response['success']) {
            $this->error = $response['message'];
            return;
        }

        $this->progress = 50;
        $this->downloadStatus = 'Seeding local database...';

        $tenantData = $response['data'];
        $seedResponse = $provisionService->seedLocalDatabase($tenantData);

        if (!$seedResponse['success']) {
            $this->error = $seedResponse['message'];
            return;
        }

        $this->progress = 90;
        $this->downloadStatus = 'Finalizing setup...';
        
        $this->downloadCounts = $seedResponse['counts'] ?? [];

        // Save server config for future sync
        $provisionService->storeServerConfig($this->serverUrl, $this->syncToken);
        
        // Mark setup complete
        DesktopHelper::markSetupComplete();

        $this->progress = 100;
        $this->downloadStatus = 'Complete!';
        $this->step = 3;
    }

    public function render()
    {
        return view('desktop.setup')->layout('components.layouts.desktop');
    }
}
