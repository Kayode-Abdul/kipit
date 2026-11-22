@php
    use App\Filament\Tenant\Pages\Dashboard;

    $routeName = optional(request()->route())->getName() ?? '';
    $skip = str_contains($routeName, 'dashboard')
        || str_contains($routeName, 'auth.')
        || str_contains($routeName, 'login')
        || str_contains($routeName, 'password-reset');

    try {
        $fallback = Dashboard::getUrl();
    } catch (\Throwable) {
        $fallback = url('/member');
    }
@endphp

@unless ($skip)
    <a
        href="{{ $fallback }}"
        x-data
        x-on:click.prevent="
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = @js($fallback);
            }
        "
        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid shadow-sm bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10"
        aria-label="{{ __('Back') }}"
        title="{{ __('Back') }}"
    >
        <x-heroicon-m-arrow-left class="h-4 w-4" />
        <span class="fi-btn-label hidden sm:inline">{{ __('Back') }}</span>
    </a>
@endunless
