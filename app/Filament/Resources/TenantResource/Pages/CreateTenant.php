<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $registerTenant = app(RegisterTenant::class);

        $formattedData = [
            'name' => strtolower($data['id']),
            'email' => $data['tenancy_email'],
            'domain' => strtolower($data['id'].'.'.config('tenancy.central_domains')[0]),
            'password' => $data['password'],
            'full_name' => $data['full_name'],
            'business_type' => $data['business_type'],
            'other_business_type' => $data['other_business_type'] ?? null,
        ];

        return $registerTenant->create($formattedData);
    }
}
