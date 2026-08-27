<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivationKeyResource\Pages;
use App\Models\ActivationKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivationKeyResource extends Resource
{
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
                Forms\Components\Toggle::make('is_used')
                    ->label('Is Used')
                    ->disabled()
                    ->default(false),
                Forms\Components\Select::make('tenant_id')
                    ->label('Used by Shop (Tenant)')
                    ->relationship('tenant', 'id')
                    ->disabled(),
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
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Used By')
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
