<?php

namespace App\Filament\Tenant\Pages;

use App\Models\ActivationKey;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Closure;

class Activation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.tenant.pages.activation';

    protected static ?string $title = 'Shop Activation';

    protected static ?string $navigationLabel = 'Activation';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->form->fill();
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
                                if (!$key) {
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
                ->label('Activate Shop')
                ->submit('submit'),
            Action::make('payWithPaystack')
                ->label('Buy Access Key via Paystack')
                ->color('success')
                ->icon('heroicon-o-credit-card')
                ->action('initializePaystackPayment'),
        ];
    }

    public function initializePaystackPayment()
    {
        $amount = (int) \App\Models\SystemSetting::get('access_key_price', 50000) * 100; // Paystack expects kobo
        $tenant = tenant();
        $user = auth()->user() ?? \App\Models\Tenants\User::first(); // Fallback if not authenticated but accessing somehow

        $response = \Illuminate\Support\Facades\Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amount,
                'reference' => 'KIPIT_'.time().'_'.$tenant->id,
                'callback_url' => url('/member/activation'),
                'metadata' => [
                    'tenant_id' => $tenant->id,
                ],
            ]);

        if ($response->successful()) {
            $this->redirect($response->json('data.authorization_url'));
            return;
        }

        Notification::make()
            ->title('Failed to initialize payment')
            ->body($response->json('message') ?? 'Please check your Paystack keys.')
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
                ->title('Shop activated successfully!')
                ->success()
                ->send();

            $this->redirect('/member');
        }
    }
}
