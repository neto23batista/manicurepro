<?php

namespace App\Listeners;

use App\Events\AgendamentoCanceladoEvent;
use App\Services\AgendaService;

class InvalidarCacheSlotsAgendamento
{
    public function __construct(private AgendaService $agendaService) {}

    public function handle(AgendamentoCanceladoEvent $event): void
    {
        $agendamento = $event->agendamento;

        $this->agendaService->invalidarCacheSlots(
            (int) $agendamento->manicure_id,
            $agendamento->data_hora_inicio,
        );
    }
}
