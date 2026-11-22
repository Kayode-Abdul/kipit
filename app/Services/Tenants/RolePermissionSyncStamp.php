<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ensures role/permission pivot rows are syncable offline events.
 */
class RolePermissionSyncStamp
{
    public function stamp(Role $role): void
    {
        if (Schema::hasColumn('roles', 'is_synced')) {
            DB::table('roles')->where('id', $role->id)->update([
                'is_synced' => false,
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('role_has_permissions') && Schema::hasColumn('role_has_permissions', 'sync_id')) {
            $rows = DB::table('role_has_permissions')->where('role_id', $role->id)->get();
            foreach ($rows as $row) {
                $payload = [
                    'is_synced' => false,
                    'updated_at' => now(),
                ];
                if (empty($row->sync_id)) {
                    $payload['sync_id'] = (string) Str::uuid();
                }
                if (Schema::hasColumn('role_has_permissions', 'id')) {
                    DB::table('role_has_permissions')->where('id', $row->id)->update($payload);
                } else {
                    DB::table('role_has_permissions')
                        ->where('role_id', $row->role_id)
                        ->where('permission_id', $row->permission_id)
                        ->update($payload);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
