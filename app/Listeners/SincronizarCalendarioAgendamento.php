<?php

namespace App\Listeners;

use App\Events\AgendamentoCriado;
use App\Events\AgendamentoReagendado;
use App\Services\CalendarOAuthService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync best-effort com calendários OAuth do usuário do agendamento.
 */
class SincronizarCalendarioAgendamento
{
    public function __construct(private CalendarOAuthService $calendario) {}

    public function handle(AgendamentoCriado|AgendamentoReagendado $event): void
    {
        try {
            $agendamento = $event->agendamento->loadMissing(['user', 'salao', 'manicure', 'servicos', 'cliente']);
            $this->calendario->syncAgendamento($agendamento);
        } catch (Throwable $e) {
            Log::warning('Listener de calendário ignorou erro.', [
                'agendamento_id' => $event->agendamento->id ?? null,
                'erro'           => $e->getMessage(),
            ]);
        }
    }
}
