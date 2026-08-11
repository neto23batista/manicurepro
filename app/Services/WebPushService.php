<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fundação Web Push — opcional.
 *
 * Sem VAPID no .env (ou sem pacote minishlink/web-push), sendToUser
 * apenas registra intent e retorna 0. Não exige chaves em produção.
 */
class WebPushService
{
    public function configurado(): bool
    {
        return filled(config('manicure.webpush.vapid.public_key'))
            && filled(config('manicure.webpush.vapid.private_key'));
    }

    /**
     * Envia notificação push para todas as subscriptions do usuário.
     *
     * @param  array<string, mixed>  $data  Payload extra (ex.: url)
     * @return int Quantidade de envios bem-sucedidos (0 no stub / sem config)
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (! $this->configurado()) {
            return 0;
        }

        $subscriptions = PushSubscription::query()
            ->where('user_id', $user->id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        // Stub: integração real (minishlink/web-push) fica para um passo seguinte.
        // Aqui só documentamos a intenção sem falhar o fluxo da aplicação.
        Log::debug('WebPush: sendToUser (stub)', [
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
            'count'   => $subscriptions->count(),
        ]);

        return 0;
    }
}
