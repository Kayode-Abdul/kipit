<?php

namespace App\Imports;

use App\Models\Tenants\Member;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MemberImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Member([
            'identity_type' => $row['identity_type'] ?? 'other',
            'identity_number' => $row['identity_number'] ?? null,
            'name' => $row['name'] ?? null,
            'code' => $row['code'] ?? null,
            'address' => $row['address'] ?? null,
            'email' => $row['email'] ?? $row['contact'] ?? null,
            'joined_date' => isset($row['joined_date']) ? Carbon::parse($row['joined_date'])->format('Y-m-d') : null,
        ]);
    }
}
