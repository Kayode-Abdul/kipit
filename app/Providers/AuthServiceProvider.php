<?php

namespace App\Providers;

use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Purchasing;
use App\Models\Tenants\Receivable;
use App\Models\Tenants\ReceivablePayment;
use App\Models\Tenants\Selling;
use App\Models\Tenants\StockOpname;
use App\Models\Tenants\Supplier;
use App\Models\Tenants\Table;
use App\Models\Tenants\User;
use App\Models\Tenants\Voucher;
use App\Policies\Tenants\CategoryPolicy;
use App\Policies\Tenants\MemberPolicy;
use App\Policies\Tenants\PaymentMethodPolicy;
use App\Policies\Tenants\PermissionPolicy;
use App\Policies\Tenants\ProductPolicy;
use App\Policies\Tenants\PurchasingPolicy;
use App\Policies\Tenants\ReceivablePaymentPolicy;
use App\Policies\Tenants\ReceivablePolicy;
use App\Policies\Tenants\RolePolicy;
use App\Policies\Tenants\SellingPolicy;
use App\Policies\Tenants\StockOpnamePolicy;
use App\Policies\Tenants\SupplierPolicy;
use App\Policies\Tenants\TablePolicy;
use App\Policies\Tenants\UserPolicy;
use App\Policies\Tenants\VoucherPolicy;
use App\Tenant;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    private $defaultAbility = [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
    ];

    protected $policies = [
        Member::class => MemberPolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Supplier::class => SupplierPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Selling::class => SellingPolicy::class,
        Purchasing::class => PurchasingPolicy::class,
        StockOpname::class => StockOpnamePolicy::class,
        Receivable::class => ReceivablePolicy::class,
        ReceivablePayment::class => ReceivablePaymentPolicy::class,
        Voucher::class => VoucherPolicy::class,
        Table::class => TablePolicy::class,
    ];

    public function register()
    {
    }

    public function boot()
    {
        $this->registerPolicies();
        Gate::after(function ($user, string $ability, ?bool $result) {
            // Central Admin users are not subject to tenant RBAC overrides.
            if (! $user instanceof User) {
                return null;
            }

            if ($user->is_owner) {
                return true;
            }

            // Standard CRUD abilities are decided by policies — never force-allow here.
            if (in_array($ability, $this->defaultAbility, true)) {
                return null;
            }

            // Desktop uses its own permission set from the local DB (offline-capable).
            $guard = function_exists('tenantPermissionGuard')
                ? tenantPermissionGuard()
                : (Auth::getDefaultDriver() === 'sanctum' ? 'sanctum' : 'web');
            $role = $user->roles->first();
            if (! $role) {
                return false;
            }

            $permissions = $role->permissions()
                ->where('guard_name', $guard)
                ->pluck('name')
                ->toArray();

            return in_array($ability, $permissions, true);
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $email = $notifiable->getEmailForPasswordReset();

            try {
                if (\Filament\Facades\Filament::getCurrentPanel()) {
                    return \Filament\Facades\Filament::getResetPasswordUrl($token, $notifiable);
                }
            } catch (\Throwable $e) {
                // Fall through to domain-based URL.
            }

            $tenant = tenant();
            if (! $tenant) {
                $host = request()->getHost();
                $tenant = Tenant::query()
                    ->whereHas('domains', fn ($q) => $q->where('domain', $host))
                    ->first();
            }

            $domain = $tenant?->domains()->first()?->domain ?? request()->getHost();
            $scheme = request()->getScheme() ?: 'https';

            return "{$scheme}://{$domain}/member/password-reset/reset?".http_build_query([
                'token' => $token,
                'email' => $email,
            ]);
        });
    }
}
