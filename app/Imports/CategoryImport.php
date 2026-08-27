<?php

namespace App\Imports;

use App\Models\Tenants\Category;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['name'])) {
            return null;
        }

        return new Category([
            'name' => $row['name'],
        ]);
    }
}
