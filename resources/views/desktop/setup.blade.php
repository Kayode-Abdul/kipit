  <div class="setup-screen">
    <div class="setup-card">
      <div style="text-align: center; margin-bottom: 24px;">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo/image-1.png'))) }}" alt="Kipit POS" style="height: 48px; object-fit: contain;">
      </div>

      {{-- Step indicators --}}
      <div class="setup-steps">
        <div class="setup-step {{ $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' }}"></div>
        <div class="setup-step {{ $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' }}"></div>
        <div class="setup-step {{ $step >= 3 ? 'active' : '' }}"></div>
      </div>

      {{-- Step 1: Connect to Server --}}
      @if($step === 1)
        <div class="setup-card-header">
          <h1>Connect to Your Store</h1>
          <p>Enter your online Kipit store details to sync your data.</p>
        </div>

        @if($error)
          <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span>{{ $error }}</span>
          </div>
        @endif

        <form wire:submit="connect">
          <div class="form-group">
            <label class="form-label" for="serverUrl">Store URL</label>
            <input type="url"
              id="serverUrl"
              class="form-input @error('serverUrl') error @enderror"
              wire:model="serverUrl"
              placeholder="https://myshop.kipitpos.com"
              required>
            @error('serverUrl')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Admin Email</label>
            <input type="email"
              id="email"
              class="form-input @error('email') error @enderror"
              wire:model="email"
              placeholder="admin@example.com"
              required>
            @error('email')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password"
              id="password"
              class="form-input @error('password') error @enderror"
              wire:model="password"
              placeholder="••••••••"
              required>
            @error('password')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>

          <button type="submit"
            class="btn btn-primary btn-full btn-lg mt-2"
            wire:loading.attr="disabled"
            wire:target="connect">
            <span wire:loading.remove wire:target="connect">Connect to Store</span>
            <span wire:loading wire:target="connect" class="flex items-center gap-1">
              <span class="spinner spinner-sm"></span>
              Connecting...
            </span>
          </button>
        </form>
      @endif

      {{-- Step 2: Downloading Data --}}
      @if($step === 2)
        <div class="setup-card-header">
          <h1>Downloading Your Data</h1>
          <p>Please wait while we sync your store data...</p>
        </div>

        @if($tenantMeta)
          <div class="alert alert-success mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Connected as <strong>{{ $tenantMeta['shop_name'] ?? 'Your Store' }}</strong></span>
          </div>
        @endif

        @if($error)
          <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span>{{ $error }}</span>
          </div>

          <button wire:click="downloadData"
            class="btn btn-primary btn-full mt-2">
            Retry Download
          </button>
        @else
          <div class="progress-container">
            <div class="progress-bar-track">
              <div class="progress-bar-fill" style="width: {{ $progress }}%"></div>
            </div>
            <div class="progress-label">
              <span>{{ $downloadStatus }}</span>
              <span>{{ $progress }}%</span>
            </div>
          </div>

          @if($progress < 100)
            <p class="progress-status">
              <span class="spinner spinner-sm"></span>
              Syncing data, please do not close the app...
            </p>
          @endif
        @endif
      @endif

      {{-- Step 3: Complete --}}
      @if($step === 3)
        <div class="success-state">
          <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem">Setup Complete!</h2>
          <p class="text-secondary">Your store data has been synced to this device.</p>

          @if($downloadCounts)
            <div class="success-stats">
              @if(isset($downloadCounts['products']))
                <div class="success-stat">
                  <div class="success-stat-value">{{ $downloadCounts['products'] }}</div>
                  <div class="success-stat-label">Products</div>
                </div>
              @endif
              @if(isset($downloadCounts['users']))
                <div class="success-stat">
                  <div class="success-stat-value">{{ $downloadCounts['users'] }}</div>
                  <div class="success-stat-label">Users</div>
                </div>
              @endif
              @if(isset($downloadCounts['categories']))
                <div class="success-stat">
                  <div class="success-stat-value">{{ $downloadCounts['categories'] }}</div>
                  <div class="success-stat-label">Categories</div>
                </div>
              @endif
              @if(isset($downloadCounts['members']))
                <div class="success-stat">
                  <div class="success-stat-value">{{ $downloadCounts['members'] }}</div>
                  <div class="success-stat-label">Customers</div>
                </div>
              @endif
            </div>
          @endif

          <a href="/member" class="btn btn-primary btn-full btn-lg mt-3">
            Enter Your Store
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </a>
        </div>
      @endif

    </div>
  </div>
