<x-filament-panels::page>
    <div class="max-w-2xl mx-auto mt-8">
        <x-filament::section>
            <div class="space-y-4">
                @if (session('error'))
                    <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 text-sm text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-400">
                        {{ session('error') }}
                    </div>
                @endif

                @if (tenant()->isActive())
                    <div class="flex items-center space-x-3 text-emerald-600 dark:text-emerald-400 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <x-heroicon-o-check-circle class="w-8 h-8" />
                        <div>
                            <h3 class="text-lg font-semibold">{{ __('Your Shop is Active') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Thank you! Your store is activated. You have full access to all features.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="space-y-2 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h3 class="text-lg font-semibold text-danger-600 dark:text-danger-400 flex items-center gap-x-2">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                            {{ __('Activation Required') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Your free trial has expired or you do not have an active subscription. Please enter your activation key below to restore access.') }}
                        </p>
                    </div>
                @endif

                <div class="space-y-2 pt-2">
                    <h3 class="text-lg font-semibold">{{ __('Extend or Top-up Subscription') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Enter an activation key if you already have one, or buy a subscription below with Paystack.') }}
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

                @php($paystackLinks = $this->getPaystackCheckoutLinks())

                <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-base font-semibold">{{ __('Buy Subscription via Paystack') }}</h3>

                    @if (empty($paystackLinks))
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No published subscription plans are available yet. Ask an admin to publish plans.') }}
                        </p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Choose a plan. You will be redirected to Paystack to complete payment.') }}
                        </p>
                        <div class="space-y-2">
                            @foreach ($paystackLinks as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 w-full"
                                    style="--c-400:var(--success-400);--c-500:var(--success-500);--c-600:var(--success-600);"
                                >
                                    <span class="fi-btn-label">
                                        {{ __('Pay :price — :label', ['price' => $link['price'], 'label' => $link['label']]) }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
