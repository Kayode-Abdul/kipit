<?php

use App\Tenant;
use App\Models\SystemSetting;
use App\Models\ActivationKey;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('determines activation status based on key or trial period', function () {
    $random = rand(0, 999999);
    // Create a Tenant
    $tenant = Tenant::create([
        'id' => 'test-shop-' . $random,
        'tenancy_db_name' => 'db_' . $random,
        'tenancy_email' => 'test' . $random . '@example.com',
    ]);

    // Manually override created_at if database overrides it
    $tenant->created_at = now();
    $tenant->save();

    // 1. Unset setting: defaults to 30 days. Tenant is active because it was created now.
    expect($tenant->isActive())->toBeTrue();
    expect($tenant->trialDaysRemaining())->toBe(30);

    // 2. Set trial days to 5. Tenant is still active.
    SystemSetting::set('free_trial_days', 5);
    expect($tenant->isActive())->toBeTrue();
    expect($tenant->trialDaysRemaining())->toBe(5);

    // 3. Set trial days to 0. Tenant should be inactive unless key exists.
    SystemSetting::set('free_trial_days', 0);
    expect($tenant->isActive())->toBeFalse();

    // 4. Create an activation key linked to the tenant. Tenant is now active.
    ActivationKey::create([
        'key' => 'ABCD-EFGH-' . $random,
        'is_used' => true,
        'tenant_id' => $tenant->id,
    ]);
    expect($tenant->isActive())->toBeTrue();
});
