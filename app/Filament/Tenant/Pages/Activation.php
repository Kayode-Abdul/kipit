<?php

namespace App\Filament\Tenant\Pages;

use App\Models\ActivationKey;
use App\Models\SubscriptionPlan;
use App\Services\PaystackActivationService;
use App\Filament\Tenant\Pages\Traits\HasPageNavigation;
use App\Traits\HasTranslatableResource;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Activation extends Page
{
    use HasPageNavigation;
    use HasTranslatableResource;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.tenant.pages.activation';

    protected static ?string $title = 'Shop Activation';

    protected static ?string $navigationLabel = 'Activation';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(PaystackActivationService $paystackActivation): void
    {
        $this->form->fill();
        $this->fulfillPaystackCallback($paystackActivation);
    }

    /**
     * Paystack redirects back with ?reference= / ?trxref=.
     * Don't rely only on webhooks — shared hosts often miss them.
     */
    protected function fulfillPaystackCallback(PaystackActivationService $paystackActivation): void
    {
        $reference = (string) (
            request()->query('reference')
            ?? request()->query('trxref')
            ?? ''
        );

        if (trim($reference) === '') {
            return;
        }

        $result = $paystackActivation->fulfillByReference($reference);

        if ($result['ok']) {
            Notification::make()
                ->title(__('Payment confirmed'))
                ->body($result['message'])
                ->success()
                ->send();

            // Drop query params so refresh doesn't re-run verify unnecessarily.
            $this->redirect('/member/activation', navigate: false);

            return;
        }

        Notification::make()
            ->title(__('Payment verification failed'))
            ->body($result['message'])
            ->danger()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('activation_key')
                    ->label(__('Activation Key'))
                    ->required()
                    ->placeholder('XXXX-XXXX-XXXX-XXXX')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, Closure $fail) {
                                $key = ActivationKey::where('key', $value)->first();
                                if (! $key) {
                                    $fail(__('The activation key is invalid.'));
                                } elseif ($key->is_used && $key->tenant_id !== tenant('id')) {
                                    $fail(__('The activation key has already been used by another shop.'));
                                }
                            };
                        },
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('Activate Shop'))
                ->submit('submit'),
        ];
    }

    /**
     * @return array<int, array{id:int,label:string,price:string,url:string}>
     */
    public function getPaystackCheckoutLinks(): array
    {
        $links = [];

        foreach ($this->getPublishedPlans() as $plan) {
            $price = $this->resolvePlanPrice($plan);
            if (! $price) {
                continue;
            }

            $months = (int) $plan->duration_in_months;
            $durationLabel = $months === 1
                ? __('1 month')
                : __(':count months', ['count' => $months]);

            $links[] = [
                'id' => (int) $plan->id,
                'label' => trim($plan->name.' — '.$durationLabel),
                'price' => price_format((float) $price->price, $price->currency_code),
                'url' => url('/member/paystack/checkout?plan_id='.$plan->id),
            ];
        }

        return $links;
    }

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    protected function getPublishedPlans(): Collection
    {
        return SubscriptionPlan::query()
            ->with('prices')
            ->where('is_published', true)
            ->orderBy('duration_in_months')
            ->get();
    }

    /**
     * @return array<int|string, string>
     */
    protected function getPublishedPlanOptions(): array
    {
        $options = [];

        foreach ($this->getPublishedPlans() as $plan) {
            $price = $this->resolvePlanPrice($plan);
            if (! $price) {
                continue;
            }

            $months = (int) $plan->duration_in_months;
            $durationLabel = $months === 1
                ? __('1 month')
                : __(':count months', ['count' => $months]);

            $options[$plan->id] = trim($plan->name.' — '.$durationLabel);
        }

        return $options;
    }

    /**
     * @return array<int|string, string>
     */
    protected function getPublishedPlanDescriptions(): array
    {
        $descriptions = [];

        foreach ($this->getPublishedPlans() as $plan) {
            $price = $this->resolvePlanPrice($plan);
            if (! $price) {
                continue;
            }

            $descriptions[$plan->id] = price_format((float) $price->price, $price->currency_code);
        }

        return $descriptions;
    }

    protected function resolvePlanPrice(SubscriptionPlan $plan): ?\App\Models\SubscriptionPlanPrice
    {
        return $plan->prices->firstWhere('currency_code', 'NGN')
            ?? $plan->prices->first();
    }

    public function initializePaystackPayment(int $planId): void
    {
        $plan = SubscriptionPlan::query()
            ->with('prices')
            ->where('is_published', true)
            ->find($planId);

        if (! $plan) {
            Notification::make()
                ->title(__('Invalid plan selected'))
                ->body(__('Please choose a published subscription plan.'))
                ->danger()
                ->send();

            return;
        }

        $price = $this->resolvePlanPrice($plan);
        if (! $price) {
            Notification::make()
                ->title(__('Plan has no price'))
                ->body(__('This plan is missing a price. Please contact support.'))
                ->danger()
                ->send();

            return;
        }

        $tenant = tenant();
        $user = auth()->user();

        if (! $user) {
            Notification::make()
                ->title(__('You must be logged in to pay'))
                ->danger()
                ->send();

            return;
        }

        $amount = (float) $price->price;
        $currency = strtoupper((string) $price->currency_code);

        try {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->timeout(20)
                ->connectTimeout(10)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => (int) round($amount * 100),
                    'currency' => $currency,
                    'reference' => 'KIPIT_'.time().'_'.$tenant->id,
                    'callback_url' => url('/member/activation'),
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                        'duration_in_months' => (int) $plan->duration_in_months,
                        'type' => 'subscription_purchase',
                    ],
                ]);
        } catch (Throwable $e) {
            Log::error('Paystack initialize request failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('Failed to initialize payment'))
                ->body(__('Could not reach Paystack. Please try again in a moment.'))
                ->danger()
                ->send();

            return;
        }

        if ($response->successful() && $response->json('data.authorization_url')) {
            $this->redirect($response->json('data.authorization_url'));

            return;
        }

        Log::error('Paystack initialize rejected', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        Notification::make()
            ->title(__('Failed to initialize payment'))
            ->body($response->json('message') ?? __('Please check your Paystack keys.'))
            ->danger()
            ->send();
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $key = ActivationKey::where('key', $data['activation_key'])->first();

        if ($key) {
            $key->update([
                'is_used' => true,
                'tenant_id' => tenant('id'),
            ]);

            Notification::make()
                ->title(__('Shop activated successfully!'))
                ->success()
                ->send();

            $this->redirect('/member');
        }
    }
}
