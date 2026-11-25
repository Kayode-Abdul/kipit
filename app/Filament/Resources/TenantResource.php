<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();

        return $admin?->isAdmin() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Shops (Tenants)';

    protected static ?string $modelLabel = 'Shop';

    protected static ?string $pluralModelLabel = 'Shops';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->label('Tenant ID / Subdomain Prefix')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('full_name')
                    ->label('Shop Name')
                    ->required(),
                Forms\Components\TextInput::make('tenancy_email')
                    ->label('Owner Email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Owner Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state) => filled($state)),
                Forms\Components\Select::make('business_type')
                    ->label('Business Type')
                    ->options([
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                        'fnb' => 'F&B',
                        'fashion' => 'Fashion',
                        'pharmacy' => 'Pharmacy',
                        'other' => 'Other',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('other_business_type')
                    ->label('Other Business Type')
                    ->visible(fn (Get $get): bool => $get('business_type') === 'other')
                    ->required(fn (Get $get): bool => $get('business_type') === 'other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Store Prefix')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Shop Name')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenancy_email')
                    ->label('Owner Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domain')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('business_type')
                    ->label('Business')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Tenant $record): string => $record->isActive() ? 'Active' : 'Inactive')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
