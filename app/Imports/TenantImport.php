<?php

namespace App\Imports;

use App\Services\RegisterTenant;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class TenantImport implements SkipsEmptyRows, OnEachRow, WithHeadingRow
{
    public function onRow(Row $row)
    {
        $data = $row->toArray();
        
        if (empty($data['full_name']) || empty($data['email'])) {
            return;
        }

        $id = $data['id'] ?? Str::slug($data['full_name']);
        
        $registerTenant = app(RegisterTenant::class);

        $formattedData = [
            'name' => strtolower($id),
            'email' => $data['email'],
            'domain' => strtolower($id.'.'.(config('tenancy.central_domains')[0] ?? 'localhost')),
            'password' => $data['password'] ?? 'password123',
            'full_name' => $data['full_name'],
            'business_type' => $data['business_type'] ?? 'other',
            'other_business_type' => $data['other_business_type'] ?? null,
        ];

        try {
            $registerTenant->create($formattedData);
        } catch (\Exception $e) {
            // Silently skip rows that might already exist or cause errors,
            // or log them if necessary.
        }
    }
}
