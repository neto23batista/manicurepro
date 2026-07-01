<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * View Composer que injeta as notificações do topbar em todas as
 * views que estendem o layout principal.
 *
 * Substitui duas queries por request:
 *   - auth()->user()->notificacoesNaoLidas()->count()
 *   - auth()->user()->notificacoes()->take(5)->get()
 *
 * Resultado: 2 queries únicas + cache de 60 segundos por usuário.
 */
class NotificacoesComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (!$user) {
            $view->with([
                'notificacoesRecentes'    => collect(),
                'notificacoesNaoLidasQtd' => 0,
            ]);
            return;
        }

        $cacheKey = "user:{$user->id}:notif_topbar";

        $payload = Cache::remember($cacheKey, config('manicure.cache_ttl.notificacoes_topbar', 60), function () use ($user) {
            return [
                'recentes'    => $user->notificacoes()->take(5)->get(),
                'naoLidasQtd' => $user->notificacoes()->where('lida', false)->count(),
            ];
        });

        $view->with([
            'notificacoesRecentes'    => $payload['recentes'],
            'notificacoesNaoLidasQtd' => $payload['naoLidasQtd'],
        ]);
    }
}
