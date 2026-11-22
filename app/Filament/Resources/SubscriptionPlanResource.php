<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Resources\SubscriptionPlanResource\RelationManagers;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPlanResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();

        return $admin?->isAdmin() ?? false;
    }

    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('duration_in_months')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Forms\Components\Toggle::make('is_published')
                            ->required()
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing (Multi-Currency)')
                    ->schema([
                        Forms\Components\Repeater::make('prices')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('currency_code')
                                    ->options(class_exists(\Symfony\Component\Intl\Currencies::class) ? \Symfony\Component\Intl\Currencies::getNames() : ['USD' => 'US Dollar', 'IDR' => 'Indonesian Rupiah', 'NGN' => 'Nigerian Naira', 'EUR' => 'Euro'])
                                    ->searchable()
                                    ->required()
                                    ->default('NGN'),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->default(0.00),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add currency price')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('prices');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_in_months')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
