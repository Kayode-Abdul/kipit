<?php

namespace App\Filament\Resources\ActivationKeyResource\Pages;

use App\Filament\Resources\ActivationKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivationKey extends EditRecord
{
    protected static string $resource = ActivationKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
