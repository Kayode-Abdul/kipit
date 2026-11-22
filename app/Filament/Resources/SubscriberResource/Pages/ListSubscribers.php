<?php

namespace App\Filament\Resources\SubscriberResource\Pages;

use App\Filament\Resources\SubscriberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('send_broadcast')
                ->label('Send Broadcast Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\TextInput::make('subject')
                        ->required()
                        ->label('Subject Line')
                        ->maxLength(255),
                    \AmidEsfahani\FilamentTinyEditor\TinyEditor::make('message')
                        ->required()
                        ->label('Email Body')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsVisibility('public')
                        ->fileAttachmentsDirectory('uploads')
                ])
                ->action(function (array $data) {
                    $subscribers = \App\Models\Subscriber::where('is_active', true)->get();
                    foreach ($subscribers as $subscriber) {
                        \Illuminate\Support\Facades\Mail::to($subscriber->email)
                            ->send(new \App\Mail\BroadcastNewsletter($data['subject'], $data['message']));
                    }
                    \Filament\Notifications\Notification::make()
                        ->title('Broadcast sent successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Send Broadcast Email to All Active Subscribers')
                ->modalDescription('Are you sure you want to send this email to everyone? This action cannot be undone and may take some time depending on the number of subscribers.'),
        ];
    }
}
