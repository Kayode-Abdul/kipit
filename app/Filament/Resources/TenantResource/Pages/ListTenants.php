<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Imports\TenantImport as ImportsTenantImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('import-tenants')
                ->label(__('Import tenants'))
                ->color('gray')
                ->form([
                    FileUpload::make('attachment')
                        ->disk(config('filesystems.upload_disk', 'public'))
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']),
                ])->action(function (array $data) {
                    $uploadDisk = config('filesystems.upload_disk', 'public');
                    $filePath = $data['attachment'];
                    $driver = config('filesystems.disks.' . $uploadDisk . '.driver', 'local');

                    if ($driver === 'local' || $driver === 'public') {
                        $fullPath = Storage::disk($uploadDisk)->path($filePath);
                        Excel::import(new ImportsTenantImport, $fullPath);
                    } else {
                        $tmpPath = tempnam(sys_get_temp_dir(), 'kipit_import_');
                        try {
                            file_put_contents($tmpPath, Storage::disk($uploadDisk)->get($filePath));
                            Excel::import(new ImportsTenantImport, $tmpPath);
                        } finally {
                            @unlink($tmpPath);
                        }
                    }
                }),
        ];
    }
}
