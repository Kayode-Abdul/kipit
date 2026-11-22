<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Shop name / business type often live on the tenant About record.
     * Hydrate empty central fields from the shop DB so edit forms are not blank.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Tenant $tenant */
        $tenant = $this->record;

        try {
            $tenant->run(function () use (&$data) {
                $about = About::query()->first();
                $owner = User::query()->owner()->first() ?? User::query()->first();

                if (blank($data['full_name'] ?? null)) {
                    $data['full_name'] = $about?->shop_name ?: ($owner?->name ?? null);
                }
                if (blank($data['business_type'] ?? null) && $about?->business_type) {
                    $data['business_type'] = $about->business_type;
                }
                if (blank($data['other_business_type'] ?? null) && $about?->other_business_type) {
                    $data['other_business_type'] = $about->other_business_type;
                }
                if (blank($data['tenancy_email'] ?? null) && $owner?->email) {
                    $data['tenancy_email'] = $owner->email;
                }
            });
        } catch (\Throwable $e) {
            // Shop SQLite missing locally — keep whatever is on the central tenant row.
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $password = $data['password'] ?? null;
        unset($data['password']);

        $record->update($data);

        try {
            $record->run(function () use ($data, $password) {
                $about = About::query()->first();
                if ($about) {
                    $about->update([
                        'shop_name' => $data['full_name'] ?? $about->shop_name,
                        'business_type' => $data['business_type'] ?? $about->business_type,
                        'other_business_type' => $data['other_business_type'] ?? $about->other_business_type,
                    ]);
                }

                $owner = User::query()->owner()->first();
                if ($owner) {
                    $owner->name = $data['full_name'] ?? $owner->name;
                    if (! empty($data['tenancy_email'])) {
                        $owner->email = $data['tenancy_email'];
                    }
                    if (filled($password)) {
                        $owner->password = bcrypt($password);
                    }
                    $owner->save();
                }
            });
        } catch (\Throwable $e) {
            // Central update still succeeded.
        }

        return $record;
    }
}
