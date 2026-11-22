<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\Report;

trait HasPageNavigation
{
    /**
     * Filament custom pages return no breadcrumbs by default, which leaves
     * users (especially on mobile/desktop webviews) without context or a way back.
     *
     * @return array<string|int, string>
     */
    public function getBreadcrumbs(): array
    {
        $crumbs = [];

        try {
            $crumbs[Dashboard::getUrl()] = __('Dashboard');
        } catch (\Throwable) {
            $crumbs[url('/member')] = __('Dashboard');
        }

        if ($this->shouldIncludeReportsParentCrumb()) {
            try {
                $crumbs[Report::getUrl()] = __('Report');
            } catch (\Throwable) {
                // Report page may be unavailable for this role.
            }
        }

        $crumbs[] = $this->getNavigationPageLabel();

        return $crumbs;
    }

    protected function shouldIncludeReportsParentCrumb(): bool
    {
        return in_array(static::class, [
            \App\Filament\Tenant\Pages\SellingReport::class,
            \App\Filament\Tenant\Pages\ProductReport::class,
            \App\Filament\Tenant\Pages\CashierReport::class,
            \App\Filament\Tenant\Pages\PurchasingReport::class,
        ], true);
    }

    protected function getNavigationPageLabel(): string
    {
        $title = trim((string) ($this->getTitle() ?? ''));
        if ($title !== '') {
            return $title;
        }

        if (property_exists(static::class, 'label') && filled(static::$label)) {
            return __(static::$label);
        }

        return __(static::getNavigationLabel());
    }

    protected function getBackNavigationUrl(): string
    {
        if ($this->shouldIncludeReportsParentCrumb()) {
            try {
                return Report::getUrl();
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return Dashboard::getUrl();
        } catch (\Throwable) {
            return url('/member');
        }
    }
}
