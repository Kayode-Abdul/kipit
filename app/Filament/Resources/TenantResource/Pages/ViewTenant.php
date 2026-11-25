<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Tenant;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        /** @var Tenant $tenant */
        $tenant = $this->getRecord();
        $tenant->loadMissing('domains');
        $profile = $this->resolveShopProfile($tenant);

        $business = $profile['business_type'] ?? null;
        if (filled($profile['other_business_type'] ?? null)) {
            $business = trim(($business ? $business.' — ' : '').$profile['other_business_type']);
        }

        $status = 'Unknown';
        $trial = '—';
        try {
            $status = $tenant->isActive() ? 'Active' : 'Inactive / expired';
            $trial = $tenant->isActiveKey()
                ? 'Licensed (activation key)'
                : ((string) $tenant->trialDaysRemaining().' trial day(s) left');
        } catch (\Throwable $e) {
            // Keep placeholders if central activation tables are unavailable.
        }

        $domains = $tenant->domains?->pluck('domain')->filter()->values();
        $domainText = ($domains && $domains->isNotEmpty()) ? $domains->implode(', ') : '—';

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shop profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Store prefix'),
                        Infolists\Components\TextEntry::make('shop_name_display')
                            ->label('Shop name')
                            ->state($profile['shop_name'] ?: '—'),
                        Infolists\Components\TextEntry::make('business_type_display')
                            ->label('Business type')
                            ->state($business ?: '—'),
                        Infolists\Components\TextEntry::make('shop_location_display')
                            ->label('Location')
                            ->state($profile['shop_location'] ?: '—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('domains_display')
                            ->label('Domain(s)')
                            ->state($domainText),
                        Infolists\Components\TextEntry::make('status_display')
                            ->label('Access status')
                            ->badge()
                            ->state($status)
                            ->color(match ($status) {
                                'Active' => 'success',
                                'Inactive / expired' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('trial_display')
                            ->label('Trial / license')
                            ->state($trial),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date joined')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Owner profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner_name_display')
                            ->label('Owner name')
                            ->state($profile['owner_name'] ?: '—'),
                        Infolists\Components\TextEntry::make('owner_email_display')
                            ->label('Owner email')
                            ->state($profile['owner_email'] ?: '—')
                            ->copyable(filled($profile['owner_email'] ?? null)),
                        Infolists\Components\TextEntry::make('tenancy_email')
                            ->label('Registered email (central)')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('owner_phone_display')
                            ->label('Phone')
                            ->state($profile['owner_phone'] ?: '—'),
                        Infolists\Components\TextEntry::make('staff_count_display')
                            ->label('Staff users in shop')
                            ->state($profile['staff_count'] !== null ? (string) $profile['staff_count'] : '—'),
                        Infolists\Components\TextEntry::make('db_status_display')
                            ->label('Shop database')
                            ->badge()
                            ->state($profile['db_ok'] ? 'Reachable' : 'Unavailable')
                            ->color($profile['db_ok'] ? 'success' : 'warning'),
                        Infolists\Components\TextEntry::make('db_error_display')
                            ->label('Database note')
                            ->state($profile['db_error'] ?: '—')
                            ->visible(! $profile['db_ok'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array{
     *   shop_name:?string,
     *   business_type:?string,
     *   other_business_type:?string,
     *   shop_location:?string,
     *   owner_name:?string,
     *   owner_email:?string,
     *   owner_phone:?string,
     *   staff_count:int|null,
     *   db_ok:bool,
     *   db_error:?string
     * }
     */
    protected function resolveShopProfile(Tenant $tenant): array
    {
        $profile = [
            'shop_name' => $tenant->full_name,
            'business_type' => $tenant->business_type,
            'other_business_type' => $tenant->other_business_type,
            'shop_location' => null,
            'owner_name' => null,
            'owner_email' => $tenant->tenancy_email,
            'owner_phone' => null,
            'staff_count' => null,
            'db_ok' => false,
            'db_error' => null,
        ];

        try {
            $dbName = $tenant->database()->getName();
            if (! $tenant->database()->manager()->databaseExists($dbName)) {
                $profile['db_error'] = "Shop database {$dbName} does not exist on this server.";

                return $profile;
            }

            $tenant->run(function () use (&$profile) {
                $about = About::query()->first();
                $owner = User::query()->owner()->first() ?? User::query()->first();

                if ($about) {
                    $profile['shop_name'] = $about->shop_name ?: $profile['shop_name'];
                    $profile['business_type'] = $about->business_type ?: $profile['business_type'];
                    $profile['other_business_type'] = $about->other_business_type ?: $profile['other_business_type'];
                    $profile['shop_location'] = $about->shop_location ?? null;
                }

                if ($owner) {
                    $profile['owner_name'] = $owner->name;
                    $profile['owner_email'] = $owner->email ?: $profile['owner_email'];
                    try {
                        $profile['owner_phone'] = $owner->profile?->phone;
                    } catch (\Throwable $e) {
                        $profile['owner_phone'] = null;
                    }
                }

                $profile['staff_count'] = User::query()->count();
                $profile['db_ok'] = true;
            });
        } catch (\Throwable $e) {
            $profile['db_ok'] = false;
            $profile['db_error'] = $e->getMessage();
        }

        return $profile;
    }
}
