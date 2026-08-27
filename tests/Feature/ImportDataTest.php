<?php

use App\Imports\CategoryImport;
use App\Imports\MemberImport;
use App\Imports\SupplierImport;
use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\Supplier;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

test('can map member import row to model', function () {
    $import = new MemberImport();
    $model = $import->model([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St',
        'identity_type' => 'other',
        'identity_number' => '123456',
    ]);

    expect($model)->toBeInstanceOf(Member::class)
        ->and($model->name)->toBe('John Doe')
        ->and($model->email)->toBe('john@example.com')
        ->and($model->address)->toBe('123 Main St')
        ->and($model->identity_number)->toBe('123456');
});

test('can map category import row to model', function () {
    $import = new CategoryImport();
    $model = $import->model([
        'name' => 'Electronics',
    ]);

    expect($model)->toBeInstanceOf(Category::class)
        ->and($model->name)->toBe('Electronics');
});

test('can map supplier import row to model', function () {
    $import = new SupplierImport();
    $model = $import->model([
        'name' => 'Acme Corp',
        'email' => 'acme@example.com',
        'city' => 'Metropolis',
    ]);

    expect($model)->toBeInstanceOf(Supplier::class)
        ->and($model->name)->toBe('Acme Corp')
        ->and($model->email)->toBe('acme@example.com')
        ->and($model->city)->toBe('Metropolis');
});
