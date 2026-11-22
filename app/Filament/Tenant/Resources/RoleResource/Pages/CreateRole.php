<?php

namespace App\Filament\Tenant\Resources\RoleResource\Pages;

use App\Filament\Tenant\Resources\RoleResource;
use App\Services\Tenants\RolePermissionSyncStamp;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        app(RolePermissionSyncStamp::class)->stamp($this->record);
    }
}
