<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Web Push — opcional (VAPID + minishlink/web-push).
 *
 * A UI de subscribe só aparece com manicure.webpush.subscribe_ui=true
 * e envioDisponivel() (VAPID + pacote). Padrão: UI off até validar ponta a ponta.
 */
class WebPushService
{
    public function configurado(): bool
    {
        return filled(config('manicure.webpush.vapid.public_key'))
            && filled(config('manicure.webpush.vapid.private_key'));
    }

    /**
     * true somente quando houver send real (minishlink) + VAPID + UI liberada.
     */
    public function envioDisponivel(): bool
    {
        if (! (bool) config('manicure.webpush.subscribe_ui', false)) {
            return false;
        }

        if (! $this->configurado()) {
            return false;
        }

        return class_exists(\Minishlink\WebPush\WebPush::class);
    }

    /**
     * Envia notificação push para todas as subscriptions do usuário.
     *
     * @param  array<string, mixed>  $data  Payload extra (ex.: url)
     * @return int Quantidade de envios bem-sucedidos
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (! $this->envioDisponivel()) {
            return 0;
        }

        $subscriptions = PushSubscription::query()
            ->where('user_id', $user->id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $webPush = $this->makeClient();
        } catch (\Throwable $e) {
            Log::warning('WebPush: falha ao inicializar cliente', [
                'user_id' => $user->id,
                'erro'    => $e->getMessage(),
            ]);

            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $enviados = 0;
        $subscriptionClass = \Minishlink\WebPush\Subscription::class;

        foreach ($subscriptions as $row) {
            $subscription = $subscriptionClass::create([
                'endpoint' => $row->endpoint,
                'publicKey' => $row->public_key,
                'authToken' => $row->auth_token,
                'contentEncoding' => $row->content_encoding ?: 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()?->getUri()?->__toString();

            if ($report->isSuccess()) {
                $enviados++;

                continue;
            }

            $code = $report->getResponse()?->getStatusCode();
            Log::info('WebPush: envio falhou', [
                'user_id'  => $user->id,
                'endpoint' => $endpoint,
                'code'     => $code,
                'reason'   => $report->getReason(),
            ]);

            if (in_array($code, [404, 410], true) && $endpoint) {
                PushSubscription::query()
                    ->where('user_id', $user->id)
                    ->where('endpoint', $endpoint)
                    ->delete();
            }
        }

        return $enviados;
    }

    private function makeClient(): object
    {
        $public = (string) config('manicure.webpush.vapid.public_key');
        $private = (string) config('manicure.webpush.vapid.private_key');
        $subject = (string) (config('manicure.webpush.vapid.subject') ?: config('app.url'));

        return new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $public,
                'privateKey' => $private,
            ],
        ]);
    }
}
