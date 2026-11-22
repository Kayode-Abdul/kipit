<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Pages\Traits\HasPageNavigation;
use App\Models\Tenants\User;
use App\Traits\HasTranslatableResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ConnectedDevices extends Page
{
    use HasPageNavigation;
    use HasTranslatableResource;

    public static ?string $label = 'Connected Devices';

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Connected Devices';

    protected static string $view = 'filament.tenant.pages.connected-devices';

    protected static ?int $navigationSort = 96;

    public static function canAccess(): bool
    {
        $user = Auth::guard('web')->user();

        if ($user?->is_owner) {
            return true;
        }

        // Prefer the dedicated permission for the active guard (web or desktop);
        // fall back for shops that have not migrated yet.
        return can('access connected devices') || can('access general setting');
    }

    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function getDevicesProperty(): Collection
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->with('tokenable')
            ->latest('last_used_at')
            ->latest('id')
            ->get();
    }

    public function revokeDevice(int $tokenId): void
    {
        $token = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereKey($tokenId)
            ->first();

        if (! $token) {
            Notification::make()
                ->title(__('Device not found'))
                ->danger()
                ->send();

            return;
        }

        $currentId = Auth::guard('web')->user()?->currentAccessToken()?->id;
        if ($currentId && (int) $currentId === (int) $token->id) {
            Notification::make()
                ->title(__('Cannot revoke the token for this session'))
                ->warning()
                ->send();

            return;
        }

        $token->delete();

        Notification::make()
            ->title(__('Device access revoked'))
            ->body(__('That device can no longer sync until it signs in again.'))
            ->success()
            ->send();
    }

    public function revokeAllDesktopDevices(): void
    {
        $query = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where(function ($q) {
                $q->where('name', 'like', 'Kipit Desktop%')
                    ->orWhere('abilities', 'like', '%desktop-sync%');
            });

        $currentId = Auth::guard('web')->user()?->currentAccessToken()?->id;
        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        $count = $query->count();
        $query->delete();

        Notification::make()
            ->title(__('Desktop devices revoked'))
            ->body(__(':count desktop sync token(s) were revoked.', ['count' => $count]))
            ->success()
            ->send();
    }

    public function revokeAllDevices(): void
    {
        $query = PersonalAccessToken::query()
            ->where('tokenable_type', User::class);

        $currentId = Auth::guard('web')->user()?->currentAccessToken()?->id;
        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        $count = $query->count();
        $query->delete();

        Notification::make()
            ->title(__('All device access revoked'))
            ->body(__(':count token(s) were revoked. Change passwords if a device was stolen.', ['count' => $count]))
            ->success()
            ->send();
    }

    public function isDesktopToken(PersonalAccessToken $token): bool
    {
        if (str_starts_with((string) $token->name, 'Kipit Desktop')) {
            return true;
        }

        $abilities = $token->abilities ?? [];

        return in_array('desktop-sync', $abilities, true);
    }
}
