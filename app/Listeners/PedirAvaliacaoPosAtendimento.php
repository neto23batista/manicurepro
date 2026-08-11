<?php

namespace App\Listeners;

use App\Events\AgendamentoFinalizado;
use App\Notifications\PedirAvaliacao;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PedirAvaliacaoPosAtendimento implements ShouldQueue
{
    public function handle(AgendamentoFinalizado $event): void
    {
        if (! config('manicure.marketing.enabled')) {
            return;
        }

        $agendamento = $event->agendamento->loadMissing(['user', 'cliente', 'manicure', 'servicos', 'salao', 'avaliacao']);

        if ($agendamento->avaliacao) {
            return;
        }

        $notification = new PedirAvaliacao($agendamento);

        $userCliente = $agendamento->user;
        if ($userCliente?->email) {
            $userCliente->notify($notification);

            return;
        }

        $email = $agendamento->cliente?->email;
        if (! $email) {
            Log::info('Marketing avaliação: sem e-mail do cliente', [
                'agendamento_id' => $agendamento->id,
            ]);

            return;
        }

        Notification::route('mail', $email)
            ->route('whatsapp', $agendamento->cliente?->telefone)
            ->notify($notification);
    }
}
