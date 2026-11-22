<?php

namespace App\Notifications;

use App\Models\Tenants\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent immediately after shop registration (sync — no queue worker required).
 */
class DomainCreated extends Notification
{
    use Queueable;

    public function __construct(
        public string $shopUrl,
        public string $shopName,
        public string $username,
        public string $passwordMask,
    ) {
    }

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Kipit POS shop is ready')
            ->markdown('emails.shop.registered', [
                'shopName' => $this->shopName,
                'shopUrl' => $this->shopUrl,
                'username' => $this->username,
                'passwordMask' => $this->passwordMask,
                'ownerName' => $notifiable->name ?: $this->username,
            ]);
    }

    public function toArray(User $notifiable): array
    {
        return [];
    }
}
