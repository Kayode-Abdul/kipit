@php
    $tenant = tenant();
@endphp

@if ($tenant && !$tenant->isActiveKey() && $tenant->isActive())
    @php
        $daysRemaining = $tenant->trialDaysRemaining();
    @endphp
    <div class="w-full bg-amber-500 dark:bg-amber-600 text-white text-center py-2 px-4 flex items-center justify-center gap-x-2 text-sm font-semibold select-none">
        <x-heroicon-o-sparkles class="w-5 h-5 animate-pulse" />
        <span>
            {{ __('You are currently on a free trial. You have') }} 
            <strong>{{ $daysRemaining }} {{ $daysRemaining === 1 ? __('day') : __('days') }}</strong> 
            {{ __('remaining before activation is required.') }}
        </span>
        <a href="{{ route('filament.tenant.pages.activation') }}" class="ml-2 underline text-white hover:text-gray-100 transition">
            {{ __('Activate now') }} &rarr;
        </a>
    </div>
@endif
