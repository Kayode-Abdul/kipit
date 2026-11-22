<?php

namespace App\Services;

use App\Constants\Role;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Notifications\DomainCreated;
use App\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterTenant
{
    /**
     * How many free-trial (unactivated) shops one email may own.
     */
    public static function freeTrialShopLimit(): int
    {
        return max(1, (int) \App\Models\SystemSetting::get('free_trial_max_shops', 2));
    }

    /**
     * Count existing unactivated shops registered under this email.
     */
    public static function freeTrialShopCountForEmail(string $email): int
    {
        return Tenant::query()
            ->get()
            ->filter(function (Tenant $tenant) use ($email) {
                return strcasecmp((string) $tenant->tenancy_email, $email) === 0
                    && ! $tenant->isActiveKey();
            })
            ->count();
    }

    public function create(array $data): Tenant
    {
        $email = (string) ($data['email'] ?? '');
        $hasActivationKey = filled($data['activation_key'] ?? null);

        // Free-trial registrations are capped per email; activation-key signups bypass the cap.
        if (! $hasActivationKey && $email !== '') {
            $limit = self::freeTrialShopLimit();
            if (self::freeTrialShopCountForEmail($email) >= $limit) {
                throw new \RuntimeException(
                    __('Free trial is limited to :count shops per email. Activate an existing shop or use an activation key to add more.', [
                        'count' => $limit,
                    ])
                );
            }
        }

        $name = $data['name'] ?? null;
        /** @var Tenant */
        $tenant = Tenant::create([
            'id' => $name,
            'tenancy_db_name' => 'kipit_'.$name,
            'tenancy_db_connection' => 'sqlite',
            'tenancy_email' => $data['email'],
            // Kept on the central tenant record so admin edit/list pages show shop details.
            'full_name' => $data['full_name'] ?? ($data['shop_name'] ?? null),
            'business_type' => $data['business_type'] ?? null,
            'other_business_type' => $data['other_business_type'] ?? null,
        ]);

        $tenant->domains()->create([
            'domain' => $data['domain'],
        ]);

        $tenant->run(function () use ($data) {
            $user = User::create([
                'name' => $data['full_name'] ?? null,
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'is_owner' => true,
            ]);

            $shopName = $data['shop_name'] ?? ($data['full_name'] ?? 'Your shop');

            About::create([
                'shop_name' => $shopName,
                'shop_location' => $data['shop_location'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'other_business_type' => $data['other_business_type'] ?? null,
            ]);

            Artisan::call('db:seed', [
                '--class' => 'PermissionSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'PaymentMethodSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
            ]);
            $user->assignRole(Role::admin);

            $shopUrl = 'https://'.ltrim((string) ($data['domain'] ?? ''), '/').'/member';
            $passwordLength = max(8, strlen((string) ($data['password'] ?? '')));

            try {
                $user->notify(new DomainCreated(
                    shopUrl: $shopUrl,
                    shopName: (string) $shopName,
                    username: (string) $data['email'],
                    passwordMask: str_repeat('*', $passwordLength),
                ));
            } catch (Throwable $e) {
                Log::error('Failed to send shop registration email', [
                    'email' => $data['email'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        return $tenant;
    }
}
