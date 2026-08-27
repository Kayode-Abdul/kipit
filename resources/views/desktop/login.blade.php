<x-layouts.desktop>
  <div class="login-screen">
    <div class="login-card">
      <div class="login-logo">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo/image-1.png'))) }}" alt="Kipit POS">
        <h1>Welcome Back</h1>
        <p>Login to your local Kipit POS</p>
      </div>

      <div class="login-status">
        <span class="status-dot online" id="network-dot"></span>
        <span id="network-text">Connected</span>
      </div>

      @if(isset($isFirstLogin) && $isFirstLogin)
        <div class="alert alert-first-login" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.1)); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#f59e0b" style="width:20px;height:20px;flex-shrink:0;margin-top:1px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
          <div>
            <strong style="color: #f59e0b; font-size: 13px;">First-Time Setup</strong>
            <p style="color: rgba(255,255,255,0.7); font-size: 12px; margin: 4px 0 0 0; line-height: 1.4;">Please log in with your <strong style="color: rgba(255,255,255,0.9);">admin or business owner</strong> account to initialize the system.</p>
          </div>
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-error">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      <form action="{{ route('desktop.login.submit') }}" method="POST">
        @csrf

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email"
            id="email"
            name="email"
            class="form-input @error('email') error @enderror"
            value="{{ old('email') }}"
            placeholder="admin@example.com"
            required
            autofocus>
          @error('email')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password"
            id="password"
            name="password"
            class="form-input"
            placeholder="••••••••"
            required>
        </div>

        <div class="login-remember">
          <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          Log In
        </button>
      </form>
    </div>
  </div>

  <script>
    // Simple network status detection
    function updateOnlineStatus() {
      const dot = document.getElementById('network-dot');
      const text = document.getElementById('network-text');
      
      if (navigator.onLine) {
        dot.className = 'status-dot online';
        text.textContent = 'Online & Ready to Sync';
      } else {
        dot.className = 'status-dot offline';
        text.textContent = 'Working Offline';
      }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Initial check
    updateOnlineStatus();
  </script>
</x-layouts.desktop>
