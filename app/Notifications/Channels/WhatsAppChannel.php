<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!config('manicure.whatsapp.enabled')) {
            return;
        }

        $token = config('manicure.whatsapp.token');
        $phoneNumberId = config('manicure.whatsapp.phone_number_id');

        if (!$token || !$phoneNumberId) {
            return;
        }

        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);
        if (!$to) {
            return;
        }

        /** @var WhatsAppMessage $message */
        $message = $notification->toWhatsApp($notifiable);
        if (!$message instanceof WhatsAppMessage) {
            return;
        }

        $version = config('manicure.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($url, $message->toPayload($to));

            if ($response->failed()) {
                Log::warning('WhatsApp: falha ao enviar', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
            }
        } catch (\Throwable $e) {
            // Nunca interrompe os demais canais (mail/database).
            Log::warning('WhatsApp: exceção ao enviar', ['erro' => $e->getMessage()]);
        }
    }
}
