<?php

namespace App\Filament\Resources\ActivationKeyResource\Pages;

use App\Filament\Resources\ActivationKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivationKeys extends ListRecords
{
    protected static string $resource = ActivationKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
