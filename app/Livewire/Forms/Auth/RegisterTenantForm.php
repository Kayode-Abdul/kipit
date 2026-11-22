<?php

namespace App\Livewire\Forms\Auth;

use App\Rules\Domain;
use App\Services\RegisterTenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Closure;

#[Layout('livewire.components.layouts.guest')]
class RegisterTenantForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function form(Form $form): Form
    {
        $freeTrialDays = (int) \App\Models\SystemSetting::get('free_trial_days', 30);

        $steps = [
            Wizard\Step::make(__('Owner\'s Account'))
                ->schema([
                    TextInput::make('full_name')
                        ->label(__('Full Name'))
                        ->string()
                        ->required(),
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->helperText(__('Free trial allows up to :count shops per email. Use an activation key to add more.', [
                            'count' => \App\Services\RegisterTenant::freeTrialShopLimit(),
                        ]))
                        ->rules([
                            function () {
                                return function (string $attribute, mixed $value, Closure $fail) {
                                    $email = strtolower(trim((string) $value));
                                    if ($email === '') {
                                        return;
                                    }

                                    // Skip the trial cap when an activation key is provided.
                                    if (filled($this->data['activation_key'] ?? null)) {
                                        return;
                                    }

                                    $limit = \App\Services\RegisterTenant::freeTrialShopLimit();
                                    if (\App\Services\RegisterTenant::freeTrialShopCountForEmail($email) >= $limit) {
                                        $fail(__('Free trial is limited to :count shops per email. Activate an existing shop or enter an activation key to continue.', [
                                            'count' => $limit,
                                        ]));
                                    }
                                };
                            },
                        ]),
                    TextInput::make('password')
                        ->password()
                        ->required()
                        ->rules(['confirmed', Password::defaults()]),
                    TextInput::make('password_confirmation')
                        ->label(__('Password Confirmation'))
                        ->password(),
                ])
                ->columns(1)
                ->icon('heroicon-o-user'),
            Wizard\Step::make(__('Shop Detail'))
                ->schema([
                    TextInput::make('shop_name')
                        ->label(__('Shop Name'))
                        ->string()
                        ->required(),
                    TextInput::make('shop_location')
                        ->label(__('Shop Location'))
                        ->string(),
                    Select::make('business_type')
                        ->label(__('Business Type'))
                        ->options([
                            'retail' => __('Retail'),
                            'wholesale' => __('Wholesale'),
                            'fnb' => __('F&B'),
                            'fashion' => __('Fashion'),
                            'pharmacy' => __('Pharmacy'),
                            'other' => __('Other'),
                        ])
                        ->live()
                        ->required(),
                    TextInput::make('other_business_type')
                        ->label(__('Other'))
                        ->visible(fn (Get $get): bool => $get('business_type') == 'other')
                        ->required(fn (Get $get): bool => $get('business_type') == 'other')
                        ->string(),
                ])
                ->icon('heroicon-o-shopping-bag'),
            Wizard\Step::make(__('Shop Domain'))
                ->schema([
                    TextInput::make('domain')
                        ->label('Domain')
                        ->rules(['unique:tenants,id', new Domain])
                        ->suffix('.'.config('tenancy.central_domains')[0])
                        ->helperText(__('No spaces are allowed in a domain name. Use letters, numbers, and hyphens only (e.g. my-shop).'))
                        ->hint(__('No spaces allowed'))
                        ->hintColor('warning')
                        ->extraInputAttributes([
                            'autocomplete' => 'off',
                            'spellcheck' => 'false',
                        ]),
                ])
                ->icon('heroicon-o-globe-alt'),
        ];

        if ($freeTrialDays <= 0) {
            $steps[] = Wizard\Step::make(__('Activation'))
                ->schema([
                    TextInput::make('activation_key')
                        ->label(__('Activation Key'))
                        ->required()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    if (empty($value)) {
                                        return;
                                    }
                                    $key = \App\Models\ActivationKey::where('key', $value)->first();
                                    if (!$key) {
                                        $fail(__('The activation key is invalid.'));
                                    } elseif ($key->is_used) {
                                        $fail(__('The activation key has already been used.'));
                                    }
                                };
                            },
                        ]),
                ])
                ->icon('heroicon-o-key');
        }

        return $form
            ->schema([
                Wizard::make($steps)
                    ->submitAction(new HtmlString(
                        Blade::render(<<<'BLADE'
                                    <x-filament::button
                                        size="sm"
                                        wire:click="create"
                                    >
                                        Submit
                                    </x-filament::button>
                                  BLADE))),
            ])
            ->statePath('data');
    }

    public function create(RegisterTenant $registerTenant): void
    {
        $data = $this->form->getState();
        $activationKeyStr = $data['activation_key'] ?? null;

        $data = array_merge($data, [
            'name' => strtolower($data['domain']),
            'domain' => strtolower($data['domain'].'.'.config('tenancy.central_domains')[0]),
        ]);

        try {
            $tenant = $registerTenant->create($data);
            
            if ($activationKeyStr) {
                $activationKey = \App\Models\ActivationKey::where('key', $activationKeyStr)->first();
                if ($activationKey) {
                    $activationKey->update([
                        'is_used' => true,
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }

            $securedDomain = 'https://'.$data['domain'].'/member';

            $this->redirect($securedDomain);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title(__('Shop limit reached'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title(__('Could not create this shop'))
                ->body(__('Please try again or contact support.'))
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.forms.auth.register');
    }
}
