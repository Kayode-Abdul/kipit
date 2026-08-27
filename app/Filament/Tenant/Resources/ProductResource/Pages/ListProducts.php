<?php

namespace App\Filament\Tenant\Resources\ProductResource\Pages;

use App\Features\ProductImport;
use App\Filament\Tenant\Resources\ProductResource;
use App\Imports\ProductImport as ImportsProductImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(\App\Filament\Tenant\Imports\ProductImporter::class)
                ->color('gray')
                ->visible(feature(ProductImport::class)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/member/products';
    }
}