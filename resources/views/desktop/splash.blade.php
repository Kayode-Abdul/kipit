<x-layouts.desktop>
  <div class="splash-screen" id="splash">
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo/image-1.png'))) }}" alt="Kipit" class="splash-logo">

    <h1 class="splash-title">
      kipit <span>POS</span>
    </h1>

    <div class="splash-loader">
      <div class="splash-loader-bar"></div>
    </div>

    <p class="splash-version">
      v{{ file_exists(base_path('version.txt')) ? trim(file_get_contents(base_path('version.txt'))) : '1.0.0' }}
    </p>
  </div>

  <script>
    // Auto-redirect after the loading animation completes
    setTimeout(function() {
      window.location.href = '{{ $redirectTo }}';
    }, 3000);
  </script>
</x-layouts.desktop>
