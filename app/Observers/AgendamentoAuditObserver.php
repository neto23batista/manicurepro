<?php

namespace App\Observers;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Services\AuditLogger;

/**
 * Writes audit_logs when an appointment transitions to cancelado.
 * Covers cliente/API/dono cancel paths without touching contested controllers.
 */
class AgendamentoAuditObserver
{
    public function updating(Agendamento $agendamento): void
    {
        if (! $agendamento->isDirty('status')) {
            return;
        }

        $cancelado = AgendamentoStatus::Cancelado->value;

        if ($agendamento->status !== $cancelado) {
            return;
        }

        if ($agendamento->getOriginal('status') === $cancelado) {
            return;
        }

        AuditLogger::log('agendamento.canceled', $agendamento, [
            'from' => $agendamento->getOriginal('status'),
            'to'   => $cancelado,
        ]);
    }
}
