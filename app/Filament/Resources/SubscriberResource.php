<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriberResource\Pages;
use App\Filament\Resources\SubscriberResource\RelationManagers;
use App\Models\Subscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriberResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();

        return $admin?->isAdmin() ?? false;
    }

    protected static ?string $model = Subscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Subscribers';

    protected static ?string $modelLabel = 'Subscriber';

    protected static ?string $pluralModelLabel = 'Subscribers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No subscribers yet')
            ->emptyStateDescription('Emails from the homepage “Notify me” form will appear here.')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_broadcast_bulk')
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
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            foreach ($records as $subscriber) {
                                if ($subscriber->is_active) {
                                    \Illuminate\Support\Facades\Mail::to($subscriber->email)
                                        ->send(new \App\Mail\BroadcastNewsletter($data['subject'], $data['message']));
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Broadcast sent to selected active subscribers successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Broadcast Email to Selected Subscribers')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscribers::route('/'),
            'create' => Pages\CreateSubscriber::route('/create'),
            'edit' => Pages\EditSubscriber::route('/{record}/edit'),
        ];
    }
}
