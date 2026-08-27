<x-layouts.desktop>
  <div class="welcome-screen" x-data="{ currentSlide: 0, totalSlides: 3 }">

    <div class="welcome-carousel">
      <div class="welcome-slides"
        :style="'transform: translateX(-' + (currentSlide * 100) + '%)'">

        {{-- Slide 1: Welcome --}}
        <div class="welcome-slide">
          <div class="welcome-slide-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l4.071-4.071A1.5 1.5 0 0 1 8.464 2h7.072a1.5 1.5 0 0 1 1.06.44l4.071 4.071a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V14.25a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75Z" />
            </svg>
          </div>
          <h2>Welcome to Kipit POS</h2>
          <p>Your powerful point-of-sale system, now running natively on your desktop. Manage inventory, process sales, and track profits — all from one app.</p>
        </div>

        {{-- Slide 2: Offline --}}
        <div class="welcome-slide">
          <div class="welcome-slide-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l8.735 8.735m0 0a.374.374 0 11.53.53m-.53-.53l.53.53m0 0L21 21M14.652 9.348a3.75 3.75 0 010 5.304m2.121-7.425a6.75 6.75 0 010 9.546m2.121-11.667c3.808 3.807 3.808 9.98 0 13.788m-9.546-4.242a3.733 3.733 0 01-1.06-2.122m-1.061 4.243a6.75 6.75 0 01-1.625-6.929m-.496 9.05c-3.068-3.067-3.664-7.67-1.79-11.334M12 12h.008v.008H12V12z" />
            </svg>
          </div>
          <h2>Work Offline, Anytime</h2>
          <p>No internet? No problem. Kipit works completely offline. Record sales, manage stock, and run your business without interruption. Your data stays safe on your device.</p>
        </div>

        {{-- Slide 3: Sync --}}
        <div class="welcome-slide">
          <div class="welcome-slide-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
          </div>
          <h2>Sync with Your Live Store</h2>
          <p>Connect to your online Kipit store to download your products, customers, and settings. Changes sync automatically when you're back online.</p>
        </div>

      </div>
    </div>

    {{-- Dots --}}
    <div class="welcome-dots">
      <template x-for="i in totalSlides">
        <button class="welcome-dot"
          :class="{ 'active': currentSlide === i - 1 }"
          @click="currentSlide = i - 1">
        </button>
      </template>
    </div>

    {{-- Actions --}}
    <div class="welcome-actions">
      <button class="btn btn-ghost"
        x-show="currentSlide > 0"
        @click="currentSlide--"
        x-transition>
        Back
      </button>

      <button class="btn btn-primary btn-lg"
        x-show="currentSlide < totalSlides - 1"
        @click="currentSlide++"
        x-transition>
        Next
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </button>

      <a href="{{ route('desktop.setup') }}"
        class="btn btn-primary btn-lg"
        x-show="currentSlide === totalSlides - 1"
        x-transition>
        Get Started
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
        </svg>
      </a>
    </div>

  </div>

  <script src="//unpkg.com/alpinejs" defer></script>
</x-layouts.desktop>
