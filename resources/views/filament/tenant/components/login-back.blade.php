<div class="mb-4 flex w-full justify-start">
  <a
    href="{{ url('/') }}"
    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid shadow-sm bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10"
    aria-label="{{ __('Back to home') }}"
    title="{{ __('Back to home') }}"
  >
    <x-heroicon-m-arrow-left class="h-4 w-4" />
    <span class="fi-btn-label">{{ __('Back') }}</span>
  </a>
</div>
