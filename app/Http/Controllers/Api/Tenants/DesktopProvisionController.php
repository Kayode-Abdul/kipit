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
     * This is the tenant's full dataset that will seed the local SQLite database.
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
        'payment_methods',
        'roles',
        'permissions',
        'vouchers',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ];

    /**
     * Authenticate a desktop app user against the live server.
     * Returns a Sanctum token + tenant/user metadata for the desktop app.
     */
    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        // Check if the tenant is active before allowing provisioning
        $tenant = tenant();
        if ($tenant && !$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shop is not active. Please activate your shop on the web before connecting a desktop app.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $token = $user->createToken($request->device_name);

        // Gather tenant metadata
        $tenantMeta = [];
        if ($tenant) {
            $tenantMeta = [
                'id' => $tenant->id,
                'created_at' => $tenant->created_at?->toISOString(),
            ];

            // Include about/shop info if available
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
                        ->where('guard_name', 'sanctum')
                        ->pluck('name')
                        ->toArray() ?? [],
                ],
            ],
        ]);
    }

    /**
     * Provision the desktop app with the full tenant dataset.
     * This is called after successful authentication and returns all tenant data.
     */
    public function provision(Request $request): JsonResponse
    {
        $tenant = tenant();
        if ($tenant && !$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shop is not active.',
            ], Response::HTTP_FORBIDDEN);
        }

        $tenantData = [];
        $tableCounts = [];

        foreach ($this->provisionTables as $tableName) {
            try {
                if (!Schema::hasTable($tableName)) {
                    continue;
                }

                $records = DB::table($tableName)->get();
                if ($records->isNotEmpty()) {
                    $tenantData[$tableName] = $records->toArray();
                    $tableCounts[$tableName] = $records->count();
                }
            } catch (\Throwable $e) {
                Log::warning("Desktop provision: failed to read table {$tableName}: {$e->getMessage()}");
            }
        }

        // Include feature flags
        $features = [];
        try {
            $features = \Laravel\Pennant\Feature::all();
        } catch (\Throwable $e) {
            // Pennant not available
        }

        // Include tenant settings
        $settings = [];
        try {
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')->get()->pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            // Settings table may not exist
        }

        return response()->json([
            'success' => true,
            'message' => 'Provisioning data ready.',
            'data' => [
                'tables' => $tenantData,
                'features' => $features,
                'settings' => $settings,
                'summary' => $tableCounts,
                'server_time' => now()->toISOString(),
            ],
        ]);
    }
}
