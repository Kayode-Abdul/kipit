<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.manage-settings';

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $title = 'System Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'free_trial_days' => (int) SystemSetting::get('free_trial_days', 30),
            'access_key_price' => (int) SystemSetting::get('access_key_price', 50000),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('free_trial_days')
                    ->label('Free Use Days / Trial Duration (Days)')
                    ->helperText('Number of days a new shop can use the application for free before needing an activation key. Set to 0 to make the activation key mandatory at registration.')
                    ->integer()
                    ->required()
                    ->minValue(0),
                TextInput::make('access_key_price')
                    ->label('Access Key Price (NGN)')
                    ->helperText('The price of the access key when purchased through Paystack.')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set('free_trial_days', (int) $data['free_trial_days']);
        SystemSetting::set('access_key_price', (int) $data['access_key_price']);

        Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();
    }
}
