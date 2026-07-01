<?php

namespace App\Listeners;

use App\Events\AgendamentoCanceladoEvent;
use App\Notifications\AgendamentoCancelado;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarAgendamentoCancelado implements ShouldQueue
{
    public function handle(AgendamentoCanceladoEvent $event): void
    {
        $agendamento = $event->agendamento;
        $notification = new AgendamentoCancelado($agendamento, $event->motivo);

        // Notifica o cliente — exceto se ele mesmo cancelou (já sabe)
        if ($event->canceladoPor !== 'cliente') {
            $userCliente = $agendamento->user;
            if ($userCliente?->email) {
                $userCliente->notify($notification);
            }
        }

        // Notifica a manicure — exceto se ela mesma cancelou
        if ($event->canceladoPor !== 'manicure') {
            $userManicure = $agendamento->manicure?->user;
            if ($userManicure?->email) {
                $userManicure->notify($notification);
            }
        }
    }
}
