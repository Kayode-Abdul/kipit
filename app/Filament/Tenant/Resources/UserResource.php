<?php

namespace App\Filament\Tenant\Resources;

use App\Features\Role;
use App\Filament\Tenant\Resources\UserResource\Pages;
use App\Models\Tenants\User;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->required(),
                Group::make()
                    ->relationship('profile')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Phone'),
                        TextInput::make('address')
                            ->label('Address'),
                    ])
                    ->columns(2),
                TextInput::make('password')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rules('confirmed'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->label('Confirm New Password')
                    ->dehydrated(false),
                Select::make('roles')
                    ->label('Roles')
                    ->visible(hasFeatureAndPermission(Role::class))
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('guard_name', tenantPermissionGuard()),
                    )
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return User::query()->whereNot('id', auth()->id());
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('profile.phone')
                    ->searchable()
                    ->label('Phone'),
                TextColumn::make('profile.address')
                    ->label('Address'),
                TextColumn::make('roles.name')
                    ->visible(hasFeatureAndPermission(Role::class))
                    ->label('Role')
                    ->badge(),
                BooleanColumn::make('is_owner'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
