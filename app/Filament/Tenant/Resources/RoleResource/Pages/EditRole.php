<?php

namespace App\Filament\Tenant\Resources\RoleResource\Pages;

use App\Filament\Tenant\Resources\RoleResource;
use App\Services\Tenants\RolePermissionSyncStamp;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(RolePermissionSyncStamp::class)->stamp($this->record);
    }
}
