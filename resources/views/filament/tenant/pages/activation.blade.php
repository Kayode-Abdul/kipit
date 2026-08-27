<x-filament-panels::page>
    <div class="max-w-2xl mx-auto mt-8">
        <x-filament::section>
            <div class="space-y-4">
                @if (tenant()->isActive())
                    <div class="flex items-center space-x-3 text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-check-circle class="w-8 h-8" />
                        <div>
                            <h3 class="text-lg font-semibold">{{ __('Your Shop is Active') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Thank you! Your store is activated. You have full access to all features.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold text-danger-600 dark:text-danger-400 flex items-center gap-x-2">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                            {{ __('Activation Required') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Your free trial has expired or you do not have an active subscription. Please enter your activation key below to restore access.') }}
                        </p>
                    </div>

                    <form wire:submit="submit" class="space-y-4 mt-6">
                        {{ $this->form }}

                        <div class="mt-4 flex justify-end">
                            <x-filament-panels::form.actions
                                :actions="$this->getFormActions()"
                            />
                        </div>
                    </form>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
