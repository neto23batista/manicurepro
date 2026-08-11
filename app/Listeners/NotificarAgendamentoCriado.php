<?php

namespace App\Listeners;

use App\Events\AgendamentoCriado;
use App\Notifications\AgendamentoConfirmado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotificarAgendamentoCriado implements ShouldQueue
{
    public function handle(AgendamentoCriado $event): void
    {
        $agendamento = $event->agendamento->loadMissing(['user', 'cliente', 'manicure.user']);

        // Notifica o cliente (User logado) ou guest com e-mail no cadastro Cliente
        $userCliente = $agendamento->user;
        if ($userCliente?->email) {
            $userCliente->notify(new AgendamentoConfirmado($agendamento));
        } elseif ($agendamento->cliente?->email) {
            Notification::route('mail', $agendamento->cliente->email)
                ->route('whatsapp', $agendamento->cliente->telefone)
                ->notify(new AgendamentoConfirmado($agendamento));
        }

        // Notifica a manicure (via User da manicure)
        $userManicure = $agendamento->manicure?->user;
        if ($userManicure?->email) {
            $userManicure->notify(new AgendamentoConfirmado($agendamento));
        }
    }
}
