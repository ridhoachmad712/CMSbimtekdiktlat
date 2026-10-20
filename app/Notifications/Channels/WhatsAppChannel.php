<?php

namespace App\Notifications\Channels;

use App\Contracts\WhatsAppNotifier;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(private WhatsAppNotifier $notifier) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor(static::class, $notification)
            ?? $notifiable->routeNotificationFor('whatsapp', $notification);

        if (blank($to) || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $this->notifier->send((string) $to, $notification->toWhatsApp($notifiable));
    }
}
