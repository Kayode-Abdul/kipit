<?php

namespace App\Imports;

use App\Models\Tenants\Supplier;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['name'])) {
            return null;
        }

        return new Supplier([
            'name' => $row['name'],
            'phone_number' => $row['phone_number'] ?? null,
            'contact_name' => $row['contact_name'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'country' => $row['country'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
        ]);
    }
}
