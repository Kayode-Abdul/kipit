<?php

use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\User;
use Filament\Facades\Filament;
use Illuminate\Support\Number;

if (! function_exists('hasFeatureAndPermission')) {
    function hasFeatureAndPermission(string $feature, ?string $permission = null): bool
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        if (! $permission) {
            return feature($feature);
        }

        return feature($feature) && $user->can($permission);
    }
}

if (! function_exists('can')) {
    function can(string $permission): bool
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user->can($permission);
    }
}

if (! function_exists('isMultiTenant')) {
    function isMultiTenant(): bool
    {
        $central_domains = config('tenancy.central_domains');
        $admin_domains = config('tenancy.admin_domains');

        if (count($central_domains) > 0 && $central_domains[0] == request()->getHost()) {
            return in_array(request()->getHost(), $admin_domains);
        }

        return false;
    }
}

if (! function_exists('price_format')) {
    function price_format(float $price, ?string $currencyCode = null): string
    {
        $currency = $currencyCode ?? Setting::get('currency', 'IDR');
        $locale = Profile::get()->locale ?? 'en';
        
        $formatted = Number::currency(
            number: $price,
            in: $currency,
            locale: $locale
        );

        if (class_exists(\Symfony\Component\Intl\Currencies::class)) {
            try {
                $symbol = \Symfony\Component\Intl\Currencies::getSymbol($currency, $locale);
                
                // If Intl returns the 3-letter code (like IDR), try to fallback to a known native symbol
                if ($symbol === $currency) {
                    $fallbackSymbols = [
                        'IDR' => 'Rp',
                        'NGN' => '₦',
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                        'JPY' => '¥',
                    ];
                    $symbol = $fallbackSymbols[$currency] ?? $symbol;
                }

                // Replace the currency abbreviation and any trailing spaces (regular or non-breaking) with the symbol
                if (str_contains($formatted, $currency)) {
                    $formatted = str_replace($currency . ' ', $symbol, $formatted);
                    $formatted = str_replace($currency . "\xc2\xa0", $symbol, $formatted);
                    $formatted = str_replace($currency, $symbol, $formatted);
                }
            } catch (\Exception $e) {
                // If symfony/intl fails for an unknown currency, fallback to default
            }
        }

        return $formatted;
    }
}
