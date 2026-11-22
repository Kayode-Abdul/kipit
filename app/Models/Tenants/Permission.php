<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use Syncable;

    protected $guarded = ['id'];
}
