<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as ModelsRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * @mixin IdeHelperRole
 */
class Role extends ModelsRole
{
    use Syncable;

    protected $guarded = ['id'];

    /**
     * Mobile app permissions (guard: sanctum).
     * Relationship kept for future use — Role form CheckboxList is commented out, not removed.
     */
    public function mobilePermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )->where('guard_name', 'sanctum');
    }

    /**
     * Web Filament UI permissions (session guard: web).
     */
    public function webPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )->where('guard_name', 'web');
    }

    /**
     * Desktop app permissions (guard: desktop). Checked locally offline from SQLite.
     */
    public function desktopPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            app(PermissionRegistrar::class)->pivotRole,
            app(PermissionRegistrar::class)->pivotPermission
        )->where('guard_name', 'desktop');
    }
}
