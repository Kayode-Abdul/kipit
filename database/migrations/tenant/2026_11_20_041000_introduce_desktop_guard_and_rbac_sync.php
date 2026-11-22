<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->addSyncColumns('permissions');
        $this->addSyncColumns('roles');
        $this->rebuildPivotWithSync('role_has_permissions', ['permission_id', 'role_id']);
        $this->rebuildPivotWithSync('model_has_roles', ['role_id', 'model_id', 'model_type']);
        $this->rebuildPivotWithSync('model_has_permissions', ['permission_id', 'model_id', 'model_type']);

        $this->cloneWebPermissionsToDesktop();
        $this->ensureDesktopAccessPermissions();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Non-destructive: keep desktop permissions and sync columns.
    }

    protected function addSyncColumns(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'sync_id')) {
                $blueprint->uuid('sync_id')->nullable()->unique();
            }
            if (! Schema::hasColumn($table, 'is_synced')) {
                $blueprint->boolean('is_synced')->default(false)->index();
            }
        });

        DB::table($table)->whereNull('sync_id')->orderBy('id')->chunkById(200, function ($rows) use ($table) {
            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update([
                    'sync_id' => (string) Str::uuid(),
                    'is_synced' => false,
                ]);
            }
        });
    }

    /**
     * Rebuild a Spatie pivot so it has id/updated_at/sync fields for offline sync events.
     *
     * @param  array<int, string>  $uniqueColumns
     */
    protected function rebuildPivotWithSync(string $table, array $uniqueColumns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'id') && Schema::hasColumn($table, 'sync_id')) {
            $rows = DB::table($table)->whereNull('sync_id')->get();
            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update([
                    'sync_id' => (string) Str::uuid(),
                    'is_synced' => false,
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $temp = $table.'_sync_tmp';
        Schema::dropIfExists($temp);

        Schema::create($temp, function (Blueprint $blueprint) use ($uniqueColumns) {
            $blueprint->id();
            foreach ($uniqueColumns as $column) {
                if ($column === 'model_type') {
                    $blueprint->string($column);
                } else {
                    $blueprint->unsignedBigInteger($column);
                }
            }
            $blueprint->uuid('sync_id')->nullable()->unique();
            $blueprint->boolean('is_synced')->default(false)->index();
            $blueprint->timestamps();
            $blueprint->unique($uniqueColumns);
        });

        $existing = DB::table($table)->get();
        foreach ($existing as $row) {
            $payload = [];
            foreach ($uniqueColumns as $column) {
                $payload[$column] = $row->{$column};
            }
            $payload['sync_id'] = (string) Str::uuid();
            $payload['is_synced'] = false;
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
            DB::table($temp)->insert($payload);
        }

        Schema::drop($table);
        Schema::rename($temp, $table);
    }

    protected function cloneWebPermissionsToDesktop(): void
    {
        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $webPermissions = DB::table('permissions')->where('guard_name', 'web')->get();
        $webToDesktop = [];

        foreach ($webPermissions as $webPermission) {
            $desktopId = DB::table('permissions')
                ->where('name', $webPermission->name)
                ->where('guard_name', 'desktop')
                ->value('id');

            if (! $desktopId) {
                $desktopId = DB::table('permissions')->insertGetId([
                    'name' => $webPermission->name,
                    'guard_name' => 'desktop',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'sync_id' => (string) Str::uuid(),
                    'is_synced' => false,
                ]);
            }

            $webToDesktop[(int) $webPermission->id] = (int) $desktopId;
        }

        $roleLinks = DB::table('role_has_permissions')->get();
        foreach ($roleLinks as $link) {
            $desktopPermissionId = $webToDesktop[(int) $link->permission_id] ?? null;
            if (! $desktopPermissionId) {
                continue;
            }

            $exists = DB::table('role_has_permissions')
                ->where('role_id', $link->role_id)
                ->where('permission_id', $desktopPermissionId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('role_has_permissions')->insert([
                'role_id' => $link->role_id,
                'permission_id' => $desktopPermissionId,
                'sync_id' => (string) Str::uuid(),
                'is_synced' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function ensureDesktopAccessPermissions(): void
    {
        $accessDesktopId = DB::table('permissions')
            ->where('name', 'access desktop')
            ->where('guard_name', 'desktop')
            ->value('id');

        if (! $accessDesktopId) {
            $accessDesktopId = DB::table('permissions')->insertGetId([
                'name' => 'access desktop',
                'guard_name' => 'desktop',
                'created_at' => now(),
                'updated_at' => now(),
                'sync_id' => (string) Str::uuid(),
                'is_synced' => false,
            ]);
        }

        $accessDevicesId = DB::table('permissions')
            ->where('name', 'access connected devices')
            ->where('guard_name', 'desktop')
            ->value('id');

        if (! $accessDevicesId) {
            $accessDevicesId = DB::table('permissions')->insertGetId([
                'name' => 'access connected devices',
                'guard_name' => 'desktop',
                'created_at' => now(),
                'updated_at' => now(),
                'sync_id' => (string) Str::uuid(),
                'is_synced' => false,
            ]);
        }

        // Also keep web connected-devices for the cloud settings UI.
        $webDevicesId = DB::table('permissions')
            ->where('name', 'access connected devices')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $webDevicesId) {
            $webDevicesId = DB::table('permissions')->insertGetId([
                'name' => 'access connected devices',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
                'sync_id' => (string) Str::uuid(),
                'is_synced' => false,
            ]);
        }

        $adminRoleIds = DB::table('roles')->where('name', 'admin')->pluck('id');
        foreach ($adminRoleIds as $roleId) {
            foreach ([$accessDesktopId, $accessDevicesId, $webDevicesId] as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'sync_id' => (string) Str::uuid(),
                    'is_synced' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Roles that can sell on web get access desktop by default.
        $createSellingWebId = DB::table('permissions')
            ->where('name', 'create selling')
            ->where('guard_name', 'web')
            ->value('id');

        if ($createSellingWebId) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $createSellingWebId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $accessDesktopId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $accessDesktopId,
                    'sync_id' => (string) Str::uuid(),
                    'is_synced' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
