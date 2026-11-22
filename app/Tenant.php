<?php

namespace App;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Stancl\Tenancy\Database\Models\Domain> $domains
 * @property-read int|null $domains_count
 * @method static \Stancl\Tenancy\Database\TenantCollection<int, static> all($columns = ['*'])
 * @method static \Stancl\Tenancy\Database\TenantCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tenant query()
 * @method static \Stancl\Tenancy\Database\TenantCollection<int, static> all($columns = ['*'])
 * @method static \Stancl\Tenancy\Database\TenantCollection<int, static> get($columns = ['*'])
 * @mixin \Eloquent
 * @mixin IdeHelperTenant
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $hidden = [
        'tenancy_db_profile_password',
    ];

    public function isActiveKey(): bool
    {
        try {
            $centralConnection = config('tenancy.database.central_connection', 'mysql');
            if (!\Illuminate\Support\Facades\Schema::connection($centralConnection)->hasTable('activation_keys')) {
                return false;
            }
            return \App\Models\ActivationKey::where('tenant_id', $this->id)
                ->where('is_used', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isActive(): bool
    {
        if ($this->isActiveKey()) {
            return true;
        }

        $trialDays = $this->getFreeTrialDaysSetting();
        if ($trialDays <= 0) {
            return false;
        }

        return $this->created_at->addDays($trialDays)->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        $trialDays = $this->getFreeTrialDaysSetting();
        if ($trialDays <= 0) {
            return 0;
        }

        $expiry = $this->created_at->addDays($trialDays);
        if (now()->greaterThanOrEqualTo($expiry)) {
            return 0;
        }

        $diffInSeconds = now()->diffInSeconds($expiry, false);

        return (int) ceil($diffInSeconds / 86400);
    }

    protected function getFreeTrialDaysSetting(): int
    {
        try {
            $centralConnection = config('tenancy.database.central_connection', 'mysql');
            if (!\Illuminate\Support\Facades\Schema::connection($centralConnection)->hasTable('system_settings')) {
                return 30; // fallback default
            }
            return (int) \App\Models\SystemSetting::get('free_trial_days', 30);
        } catch (\Throwable $e) {
            return 30; // fallback default
        }
    }
}
