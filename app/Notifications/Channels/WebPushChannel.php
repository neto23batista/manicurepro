<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Notifications\Notification;

/**
 * Canal Laravel opcional. Notificações podem declarar toWebPush($notifiable)
 * retornando ['title' => ..., 'body' => ..., 'data' => [...]].
 * Sem VAPID configurado o envio é no-op (igual WhatsAppChannel).
 */
class WebPushChannel
{
    public function __construct(private WebPushService $webPush) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->webPush->configurado()) {
            return;
        }

        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);
        if (! is_array($payload) || empty($payload['title'])) {
            return;
        }

        if (! method_exists($notifiable, 'getKey')) {
            return;
        }

        // sendToUser espera User; notifiables tipicamente são User.
        if (! $notifiable instanceof User) {
            return;
        }

        $this->webPush->sendToUser(
            $notifiable,
            (string) $payload['title'],
            (string) ($payload['body'] ?? ''),
            is_array($payload['data'] ?? null) ? $payload['data'] : [],
        );
    }
}
