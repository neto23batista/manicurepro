<?php

namespace App\Listeners;

use App\Events\AgendamentoCanceladoEvent;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort: cancela/estorna cobrança Pix na Mercado Pago ao cancelar o agendamento.
 */
class CancelarPagamentoMercadoPago
{
    public function __construct(private MercadoPagoService $mp) {}

    public function handle(AgendamentoCanceladoEvent $event): void
    {
        try {
            $this->mp->cancelarOuEstornar($event->agendamento);
        } catch (\Throwable $e) {
            Log::warning('CancelarPagamentoMercadoPago falhou', [
                'agendamento_id' => $event->agendamento->id,
                'erro'           => $e->getMessage(),
            ]);
        }
    }
}
