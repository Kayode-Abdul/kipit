<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DesktopProvisionController extends Controller
{
    /**
     * Tables to include in the desktop provisioning payload.
     */
    protected array $provisionTables = [
        'users',
        'categories',
        'products',
        'members',
        'sellings',
        'selling_details',
        'stocks',
        'suppliers',
        'purchasings',
        'stock_opnames',
        'stock_opname_items',
        'receivables',
        'receivable_items',
        'receivable_payments',
        'cash_drawers',
        'cart_items',
        'abouts',
        'settings',
        'features',
        'profiles',
        'payment_methods',
        'roles',
        'permissions',
        'vouchers',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ];

    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $tenant = tenant();
        if ($tenant && ! $tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shop is not active. Please activate your shop on the web before connecting a desktop app.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (! $this->userMayConnectDesktop($user)) {
            Auth::guard('web')->logout();

            return response()->json([
                'success' => false,
                'message' => 'This user role is not allowed to connect a desktop device. Ask an admin to enable “access desktop” for the role.',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken($request->device_name, ['desktop-sync']);

        $tenantMeta = [];
        if ($tenant) {
            $tenantMeta = [
                'id' => $tenant->id,
                'created_at' => $tenant->created_at?->toISOString(),
            ];

            try {
                if (Schema::hasTable('abouts')) {
                    $about = DB::table('abouts')->first();
                    if ($about) {
                        $tenantMeta['shop_name'] = $about->shop_name ?? null;
                        $tenantMeta['business_type'] = $about->business_type ?? null;
                    }
                }
            } catch (\Throwable $e) {
                // Silently continue
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'data' => [
                'token' => $token->plainTextToken,
                'tenant' => $tenantMeta,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles()->first()?->name,
                    'permissions' => $user->roles()->first()?->permissions()
                        ->where('guard_name', 'desktop')
                        ->pluck('name')
                        ->toArray() ?? [],
                ],
            ],
        ]);
    }

    /**
     * Provision desktop data in bounded chunks.
     *
     * Query params:
     * - table: optional single table name
     * - after_id: resume cursor (default 0)
     * - limit: page size (default config sync.batch_size)
     * - paginated=1: chunked mode (preferred for large tenants)
     *
     * Without paginated=1 and without table, returns a lightweight manifest
     * so clients can walk tables page-by-page (avoids full-table memory dumps).
     */
    public function provision(Request $request): JsonResponse
    {
        $tenant = tenant();
        if ($tenant && ! $tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shop is not active.',
            ], Response::HTTP_FORBIDDEN);
        }

        $limit = max(50, min((int) $request->input('limit', config('sync.batch_size', 500)), 2000));
        $afterId = max(0, (int) $request->input('after_id', 0));
        $requestedTable = $request->input('table');
        $paginated = $request->boolean('paginated', true);

        $features = [];
        try {
            $features = \Laravel\Pennant\Feature::all();
        } catch (\Throwable $e) {
            // Pennant not available
        }

        $settings = [];
        try {
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')->get()->pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            // Settings table may not exist
        }

        $availableTables = array_values(array_filter(
            $this->provisionTables,
            fn (string $table) => Schema::hasTable($table)
        ));

        // Manifest: list tables + counts without loading rows
        if ($paginated && ! $requestedTable) {
            $summary = [];
            foreach ($availableTables as $tableName) {
                try {
                    $summary[$tableName] = DB::table($tableName)->count();
                } catch (\Throwable $e) {
                    Log::warning("Desktop provision: failed to count {$tableName}: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Provisioning manifest ready.',
                'data' => [
                    'mode' => 'manifest',
                    'tables' => $availableTables,
                    'summary' => $summary,
                    'features' => $features,
                    'settings' => $settings,
                    'batch_size' => $limit,
                    'server_time' => now()->toISOString(),
                ],
            ]);
        }

        if ($requestedTable) {
            if (! in_array($requestedTable, $this->provisionTables, true) || ! Schema::hasTable($requestedTable)) {
                return response()->json([
                    'success' => false,
                    'message' => "Unknown or unavailable table: {$requestedTable}",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            [$records, $nextAfterId, $hasMore] = $this->fetchTableChunk($requestedTable, $afterId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Provisioning chunk ready.',
                'data' => [
                    'mode' => 'chunk',
                    'table' => $requestedTable,
                    'records' => $records,
                    'after_id' => $afterId,
                    'next_after_id' => $nextAfterId,
                    'has_more' => $hasMore,
                    'count' => count($records),
                    'features' => $features,
                    'settings' => $settings,
                    'server_time' => now()->toISOString(),
                ],
            ]);
        }

        // Full-dump mode removed: large tenants OOMed the API. Clients must use
        // paginated=1 (manifest) then table + after_id chunks.
        return response()->json([
            'success' => false,
            'message' => 'Full desktop provision is disabled. Request with paginated=1 (manifest) or table + after_id.',
            'data' => [
                'mode' => 'manifest_required',
                'tables' => $availableTables,
                'batch_size' => $limit,
                'server_time' => now()->toISOString(),
            ],
        ], Response::HTTP_GONE);
    }

    /**
     * Desktop connect uses the desktop permission set (not web/mobile).
     * Owners always; otherwise requires desktop “access desktop”,
     * with a temporary fallback until shops migrate.
     */
    protected function userMayConnectDesktop(User $user): bool
    {
        if ($user->is_owner) {
            return true;
        }

        $role = $user->roles()->first();
        if (! $role) {
            return false;
        }

        $desktopPermissionNames = $role->permissions()
            ->where('guard_name', 'desktop')
            ->pluck('name')
            ->all();

        if (in_array('access desktop', $desktopPermissionNames, true)) {
            return true;
        }

        // Backward-compatible fallback until tenant migrations seed desktop perms.
        $webPermissionNames = $role->permissions()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        return in_array('create selling', $webPermissionNames, true)
            || in_array('access general setting', $webPermissionNames, true);
    }

    /**
     * Fetch a bounded page of rows. Prefer id cursors; fall back to offset for
     * pivot tables without an id column (e.g. Spatie permission pivots).
     *
     * @return array{0: array<int, object>, 1: int, 2: bool}
     */
    protected function fetchTableChunk(string $tableName, int $afterId, int $limit): array
    {
        if (Schema::hasColumn($tableName, 'id')) {
            $collection = DB::table($tableName)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get();

            $nextAfterId = $collection->isNotEmpty() ? (int) $collection->last()->id : $afterId;
            $hasMore = $collection->count() === $limit
                && DB::table($tableName)->where('id', '>', $nextAfterId)->exists();

            return [$collection->all(), $nextAfterId, $hasMore];
        }

        // Offset-based paging for composite-key pivots (usually small).
        $offset = max(0, $afterId);
        $collection = DB::table($tableName)->offset($offset)->limit($limit)->get();
        $nextAfterId = $offset + $collection->count();
        $hasMore = $collection->count() === $limit;

        return [$collection->all(), $nextAfterId, $hasMore];
    }
}
