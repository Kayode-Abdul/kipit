<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;

/**
 * Sync (non-queued) password reset mail for Filament.
 * Filament's bundled notification implements ShouldQueue, which silently
 * fails on hosts without a running queue worker.
 */
class FilamentResetPassword extends BaseNotification
{
    public string $url;

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }
}
