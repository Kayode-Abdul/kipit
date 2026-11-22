@props([
    'actions' => [],
    'breadcrumbs' => [],
    'heading',
    'subheading' => null,
])

@php
    use App\Filament\Tenant\Pages\Dashboard;

    try {
        $homeUrl = Dashboard::getUrl();
    } catch (\Throwable) {
        $homeUrl = url('/member');
    }

    $routeName = optional(request()->route())->getName() ?? '';
    $showMobileBack = $breadcrumbs
        && ! str_contains($routeName, 'dashboard')
        && ! str_contains($routeName, 'auth.')
        && ! str_contains($routeName, 'login');
@endphp

<header
    {{ $attributes->class(['fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}
>
    <div class="min-w-0 flex-1">
        {{-- Mobile: always-visible back + compact breadcrumbs (Filament hides crumbs on small screens by default) --}}
        @if ($showMobileBack)
            <div class="mb-2 flex items-center gap-2 sm:hidden">
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
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700 shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20"
                    aria-label="{{ __('Back') }}"
                >
                    <x-heroicon-m-arrow-left class="h-5 w-5" />
                </a>
                <nav class="min-w-0 flex-1 overflow-hidden text-sm text-gray-500 dark:text-gray-400">
                    <ol class="flex items-center gap-1 overflow-x-auto whitespace-nowrap">
                        @foreach ($breadcrumbs as $url => $label)
                            <li class="flex items-center gap-1">
                                @if (! $loop->first)
                                    <span class="text-gray-400">/</span>
                                @endif
                                @if (is_string($url) && ! $loop->last)
                                    <a href="{{ $url }}" class="truncate text-kipit-primary hover:underline">{{ $label }}</a>
                                @else
                                    <span class="truncate font-medium text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        @endif

        @if ($breadcrumbs)
            <x-filament::breadcrumbs
                :breadcrumbs="$breadcrumbs"
                class="mb-2 hidden sm:block"
            />
        @endif

        <h1
            class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl"
        >
            {{ $heading }}
        </h1>

        @if ($subheading)
            <p
                class="fi-header-subheading mt-2 max-w-2xl text-lg text-gray-600 dark:text-gray-400"
            >
                {{ $subheading }}
            </p>
        @endif
    </div>

    <div
        @class([
            'flex shrink-0 items-center gap-3',
            'sm:mt-7' => $breadcrumbs,
        ])
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes()) }}

        @if ($actions)
            <x-filament::actions :actions="$actions" />
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</header>
