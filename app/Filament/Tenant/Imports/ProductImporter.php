<?php

namespace App\Filament\Tenant\Imports;

use App\Models\Tenants\Product;
use App\Models\Tenants\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('sku')
                ->label('SKU')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('category')
                ->requiredMapping()
                ->relationship(resolveUsing: ['name'])
                ->rules(['required']),
            ImportColumn::make('initial_price')
                ->label('Cost Price (Initial)')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('selling_price')
                ->label('Selling Price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('stock')
                ->label('Initial Stock')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('unit')
                ->label('Unit (e.g. PCS, KG)')
                ->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $sku = $this->data['sku'] ?? Str::upper(Str::random(8));

        return Product::firstOrNew([
            'sku' => $sku,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
