<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $connectedDevicesWeb = Permission::firstOrCreate([
            'name' => 'access connected devices',
            'guard_name' => 'web',
        ]);
        $desktopWeb = Permission::firstOrCreate([
            'name' => 'access desktop',
            'guard_name' => 'web',
        ]);
        $desktopSanctum = Permission::firstOrCreate([
            'name' => 'access desktop',
            'guard_name' => 'sanctum',
        ]);

        // This app keeps one role row (usually guard web) and attaches both web + sanctum
        // permissions on the pivot — same pattern as PermissionSeeder / RoleResource.
        $admin = Role::query()->where('name', 'admin')->first();
        if ($admin) {
            $this->attach($admin, $connectedDevicesWeb);
            $this->attach($admin, $desktopWeb);
            $this->attach($admin, $desktopSanctum);
        }

        $this->grantToRolesWithPermission('access general setting', 'web', [$connectedDevicesWeb, $desktopWeb, $desktopSanctum]);
        $this->grantToRolesWithPermission('create selling', 'web', [$desktopWeb, $desktopSanctum]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $ids = Permission::query()
            ->whereIn('name', ['access connected devices', 'access desktop'])
            ->pluck('id');

        if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        Permission::query()
            ->whereIn('name', ['access connected devices', 'access desktop'])
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * @param  array<int, Permission>  $grants
     */
    protected function grantToRolesWithPermission(string $existingPermission, string $guard, array $grants): void
    {
        $permissionId = Permission::query()
            ->where('name', $existingPermission)
            ->where('guard_name', $guard)
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $role = Role::query()->find($roleId);
            if (! $role) {
                continue;
            }
            foreach ($grants as $grant) {
                $this->attach($role, $grant);
            }
        }
    }

    protected function attach(Role $role, Permission $permission): void
    {
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
};
