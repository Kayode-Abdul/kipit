@php
    use App\Filament\Tenant\Pages\Dashboard;
    use Filament\Facades\Filament;

    $routeName = optional(request()->route())->getName() ?? '';
    $skip = str_contains($routeName, 'dashboard')
        || str_contains($routeName, 'auth.')
        || str_contains($routeName, 'login')
        || str_contains($routeName, 'password-reset')
        || str_contains($routeName, 'cashier')
        || str_contains($routeName, 'p-o-s')
        || str_contains($routeName, 'cart-item');

    try {
        $homeUrl = Dashboard::getUrl();
    } catch (\Throwable) {
        $homeUrl = url('/member');
    }

    $label = __('Dashboard');
    try {
        $page = Filament::getCurrentPanel()?->getId() ? true : false;
        if (isset($this) && method_exists($this, 'getTitle')) {
            $title = trim((string) ($this->getTitle() ?? ''));
            if ($title !== '') {
                $label = $title;
            }
        }
    } catch (\Throwable) {
        // ignore
    }
@endphp

@unless ($skip)
    <div class="sticky top-0 z-30 -mx-4 mb-4 border-b border-gray-200 bg-white/95 px-4 py-2 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95 sm:hidden">
        <div class="flex items-center gap-3">
            <a
                href="{{ $homeUrl }}"
                x-data
                x-on:click.prevent="
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = @js($homeUrl);
                    }
                "
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                aria-label="{{ __('Back') }}"
            >
                <x-heroicon-m-arrow-left class="h-5 w-5" />
            </a>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $label }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ __('Tap back to leave this page') }}</p>
            </div>
            <a href="{{ $homeUrl }}" class="truncate text-xs font-medium text-kipit-primary">{{ __('Home') }}</a>
        </div>
    </div>
@endunless
