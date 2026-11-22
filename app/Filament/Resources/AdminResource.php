<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Team Users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        return $admin?->canManageAdmins() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? strtolower(trim($state)) : null),
            Forms\Components\Select::make('role')
                ->options([
                    Admin::ROLE_ADMIN => 'Admin (full access)',
                    Admin::ROLE_WRITER => 'Blog writer',
                ])
                ->required()
                ->default(Admin::ROLE_WRITER)
                ->helperText('Blog writers can create/edit posts only. Admins manage tickets, tenants, and team users.'),
            // Store plaintext here — Admin model `password` cast hashes once (avoid double-hash).
            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->helperText(fn (string $operation): ?string => $operation === 'edit'
                    ? 'Leave blank to keep the current password.'
                    : 'Writer signs in at /admin/login with this email and password.')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Admin::ROLE_WRITER ? 'Blog writer' : 'Admin'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Admin $record): bool => $record->id !== Auth::guard('admin')->id()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
