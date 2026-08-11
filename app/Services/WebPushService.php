<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fundação Web Push — opcional.
 *
 * Sem o pacote minishlink/web-push NÃO há envio real. A UI de subscribe
 * fica desligada (ver manicure.webpush.subscribe_ui / envioDisponivel()).
 * Não inventar send falso: sendToUser só loga e retorna 0.
 */
class WebPushService
{
    public function configurado(): bool
    {
        return filled(config('manicure.webpush.vapid.public_key'))
            && filled(config('manicure.webpush.vapid.private_key'));
    }

    /**
     * true somente quando houver send real (minishlink) + VAPID.
     * Usado para expor meta tags / pedir permissão no browser.
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
     * @return int Quantidade de envios bem-sucedidos (0 sem minishlink / sem config)
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

        // Stub honesto: pacote detectado, mas integração de send ainda não implementada.
        // Não retornar count falso — 0 até haver envio real.
        Log::debug('WebPush: sendToUser ainda não implementado (minishlink presente)', [
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
            'count'   => $subscriptions->count(),
        ]);

        return 0;
    }
}
