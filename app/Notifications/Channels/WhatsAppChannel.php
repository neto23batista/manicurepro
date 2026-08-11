<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        try {
            $this->enviar($notifiable, $notification);
        } catch (\Throwable $e) {
            // Nunca interrompe os demais canais (mail/database).
            Log::warning('WhatsApp: exceção ao enviar', [
                'excecao' => $e::class,
                'erro'    => $this->mensagemSegura($e->getMessage()),
            ]);
        }
    }

    private function enviar(object $notifiable, Notification $notification): void
    {
        if (! config('manicure.whatsapp.enabled')) {
            return;
        }

        $token = config('manicure.whatsapp.token');
        $phoneNumberId = config('manicure.whatsapp.phone_number_id');

        if (! $token || ! $phoneNumberId) {
            return;
        }

        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);
        if (! $to) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        if (! $message instanceof WhatsAppMessage) {
            return;
        }

        $version = config('manicure.whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $message->toPayload($to));

        if ($response->failed()) {
            Log::warning('WhatsApp: falha ao enviar', $this->resumoErroResposta($response));
        }
    }

    /**
     * Extrai só campos seguros da resposta da Graph API (sem dump completo).
     *
     * @return array{status:int, error_code:mixed, error_type:mixed, error_message:?string}
     */
    private function resumoErroResposta(Response $response): array
    {
        $json = $response->json();
        $error = is_array($json) ? ($json['error'] ?? null) : null;

        return [
            'status'        => $response->status(),
            'error_code'    => is_array($error) ? ($error['code'] ?? null) : null,
            'error_type'    => is_array($error) ? ($error['type'] ?? null) : null,
            'error_message' => is_array($error)
                ? $this->mensagemSegura((string) ($error['message'] ?? ''))
                : null,
        ];
    }

    /**
     * Remove tokens/Bearer da mensagem antes de logar.
     */
    private function mensagemSegura(string $mensagem): string
    {
        $mensagem = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $mensagem) ?? $mensagem;

        $token = config('manicure.whatsapp.token');
        if (is_string($token) && $token !== '') {
            $mensagem = str_replace($token, '[redacted]', $mensagem);
        }

        return $mensagem;
    }
}
