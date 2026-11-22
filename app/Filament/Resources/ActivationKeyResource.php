<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivationKeyResource\Pages;
use App\Models\ActivationKey;
use App\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivationKeyResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();

        return $admin?->isAdmin() ?? false;
    }

    protected static ?string $model = ActivationKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Activation Keys';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->default(fn () => Str::random(16))
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('tenant_id')
                    ->label('Shop (Tenant)')
                    ->helperText('Assign this key to a shop now, or leave empty until the shop redeems it. You can change the shop later.')
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn ($query) => $query->orderBy('id'),
                    )
                    ->getOptionLabelFromRecordUsing(function (Tenant $record): string {
                        $email = $record->tenancy_email ?: 'no email';
                        $name = $record->full_name ?: $record->id;

                        return "{$record->id} — {$name} ({$email})";
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Toggle::make('is_used')
                    ->label('Marked as used')
                    ->helperText('Turn on when this key has activated a shop. Admins can correct this if needed.')
                    ->default(false),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At (Optional)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Key copied to clipboard!'),
                Tables\Columns\IconColumn::make('is_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('tenant.id')
                    ->label('Shop')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.tenancy_email')
                    ->label('Owner email')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivationKeys::route('/'),
            'create' => Pages\CreateActivationKey::route('/create'),
            'edit' => Pages\EditActivationKey::route('/{record}/edit'),
        ];
    }
}
