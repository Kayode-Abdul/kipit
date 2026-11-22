<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\RoleResource\Pages;
use App\Models\Tenants\Role;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->translateLabel()
                    ->required(),
                CheckboxList::make('webPermissions')
                    ->label(__('Web app permissions'))
                    ->helperText(__('Permissions for the web panel (guard: web). Separate from desktop.'))
                    ->bulkToggleable()
                    ->columns(4)
                    ->relationship(
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('guard_name', 'web')
                    )
                    ->noSearchResultsMessage('No permissions found.')
                    ->searchable(),
                CheckboxList::make('desktopPermissions')
                    ->label(__('Desktop app permissions'))
                    ->helperText(__('Permissions for the desktop app (guard: desktop). Stored locally and enforced offline from the device SQLite database; changes sync when online.'))
                    ->bulkToggleable()
                    ->columns(4)
                    ->relationship(
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('guard_name', 'desktop')
                    )
                    ->noSearchResultsMessage('No permissions found.')
                    ->searchable(),
                // Mobile app permissions (guard: sanctum) — kept for future use; do not delete.
                // CheckboxList::make('mobilePermissions')
                //     ->label(__('Mobile app permissions'))
                //     ->helperText(__('API/token permissions (guard: sanctum). Used by the mobile/API client. Separate from web/desktop UI permissions.'))
                //     ->bulkToggleable()
                //     ->columns(4)
                //     ->relationship(
                //         titleAttribute: 'name',
                //         modifyQueryUsing: fn ($query) => $query->where('guard_name', 'sanctum')
                //     )
                //     ->noSearchResultsMessage('No permissions found.')
                //     ->searchable(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
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

    public static function getEloquentQuery(): Builder
    {
        // List web roles only; desktop permissions are edited on the same role via desktopPermissions.
        return parent::getEloquentQuery()->where('guard_name', 'web');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'create' => Pages\CreateRole::route('/create'),
        ];
    }
}
