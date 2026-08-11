<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Cliente;
use App\Models\Salao;
use App\Notifications\ReativarCliente;
use App\Notifications\SugerirRetorno;
use Carbon\Carbon;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;

/**
 * Campanhas de retenção (reativação / sugestão de retorno).
 * Respeita o gate config('manicure.marketing.enabled').
 */
class MarketingService
{
    public function __construct(private ClienteSegmentacao $crm) {}

    public function habilitado(): bool
    {
        return (bool) config('manicure.marketing.enabled', false);
    }

    /**
     * Envia campanha de reativação para clientes inativos (CRM), com cooldown.
     *
     * @return array{enviados: int, pulados: int, candidatos: int}
     */
    public function reativarInativos(Salao $salao): array
    {
        $cooldown = (int) config('manicure.marketing.reativar.cooldown_dias', 30);
        $cutoffCooldown = now()->subDays($cooldown);
        $comCupom = (bool) config('manicure.marketing.reativar.com_cupom', true);

        $query = Cliente::query()
            ->where('salao_id', $salao->id)
            ->where('ativo', true)
            ->where(function ($q) use ($cutoffCooldown) {
                $q->whereNull('reativacao_enviada_em')
                    ->orWhere('reativacao_enviada_em', '<', $cutoffCooldown);
            });

        $this->crm->aplicarFiltro($query, 'inativo');

        $clientes = $query->get();
        $enviados = 0;
        $pulados = 0;

        foreach ($clientes as $cliente) {
            if (! $this->temCanalContato($cliente)) {
                $pulados++;

                continue;
            }

            $cupom = null;
            if ($comCupom) {
                try {
                    $cupom = $this->crm->gerarCupomReativacao($cliente);
                } catch (\Throwable $e) {
                    Log::warning('Marketing reativar: falha ao gerar cupom', [
                        'cliente_id' => $cliente->id,
                        'erro'       => $e->getMessage(),
                    ]);
                }
            }

            if (! $this->claimTimestamp($cliente, 'reativacao_enviada_em', $cutoffCooldown)) {
                continue;
            }

            try {
                $this->notificar($cliente, new ReativarCliente($cliente, $salao->nome, $cupom));
                $enviados++;
            } catch (\Throwable $e) {
                $cliente->forceFill(['reativacao_enviada_em' => null])->save();
                Log::error('Marketing reativar: falha ao notificar', [
                    'cliente_id' => $cliente->id,
                    'erro'       => $e->getMessage(),
                ]);
            }
        }

        return [
            'enviados'   => $enviados,
            'pulados'    => $pulados,
            'candidatos' => $clientes->count(),
        ];
    }

    /**
     * Sugere retorno por cadência (última visita concluída há ~N dias).
     *
     * @return array{enviados: int, pulados: int, candidatos: int}
     */
    public function sugerirRetornos(Salao $salao): array
    {
        $cadencia = (int) config('manicure.marketing.retorno.cadencia_dias', 28);
        $janela = (int) config('manicure.marketing.retorno.janela_dias', 3);
        $cooldown = (int) config('manicure.marketing.retorno.cooldown_dias', 25);

        $fimJanela = now()->subDays($cadencia)->endOfDay();
        $inicioJanela = now()->subDays($cadencia + $janela)->startOfDay();
        $cutoffCooldown = now()->subDays($cooldown);
        $concluido = AgendamentoStatus::Concluido->value;

        $ultimaSql = '(SELECT MAX(data_hora_inicio) FROM agendamentos'
            .' WHERE agendamentos.cliente_id = clientes.id'
            .' AND agendamentos.status = ?)';

        $clientes = Cliente::query()
            ->where('salao_id', $salao->id)
            ->where('ativo', true)
            ->where('total_visitas', '>=', 1)
            ->whereRaw("{$ultimaSql} BETWEEN ? AND ?", [
                $concluido,
                $inicioJanela,
                $fimJanela,
            ])
            ->where(function ($q) use ($cutoffCooldown) {
                $q->whereNull('retorno_sugerido_em')
                    ->orWhere('retorno_sugerido_em', '<', $cutoffCooldown);
            })
            // Sem agendamento futuro aberto.
            ->whereDoesntHave('agendamentos', function ($q) {
                $q->where('data_hora_inicio', '>', now())
                    ->whereNotIn('status', [
                        AgendamentoStatus::Cancelado->value,
                        AgendamentoStatus::NaoCompareceu->value,
                    ]);
            })
            ->get();

        $enviados = 0;
        $pulados = 0;

        foreach ($clientes as $cliente) {
            if (! $this->temCanalContato($cliente)) {
                $pulados++;

                continue;
            }

            if (! $this->claimTimestamp($cliente, 'retorno_sugerido_em', $cutoffCooldown)) {
                continue;
            }

            try {
                $this->notificar($cliente, new SugerirRetorno($cliente, $salao->nome, $cadencia));
                $enviados++;
            } catch (\Throwable $e) {
                $cliente->forceFill(['retorno_sugerido_em' => null])->save();
                Log::error('Marketing retorno: falha ao notificar', [
                    'cliente_id' => $cliente->id,
                    'erro'       => $e->getMessage(),
                ]);
            }
        }

        return [
            'enviados'   => $enviados,
            'pulados'    => $pulados,
            'candidatos' => $clientes->count(),
        ];
    }

    private function temCanalContato(Cliente $cliente): bool
    {
        return filled($cliente->email) || filled($cliente->telefone);
    }

    /**
     * Claim atômico do timestamp de envio (idempotência sob overlap de cron).
     */
    private function claimTimestamp(Cliente $cliente, string $coluna, Carbon $cutoffCooldown): bool
    {
        $claimed = Cliente::whereKey($cliente->id)
            ->where(function ($q) use ($coluna, $cutoffCooldown) {
                $q->whereNull($coluna)
                    ->orWhere($coluna, '<', $cutoffCooldown);
            })
            ->update([$coluna => now()]);

        return $claimed > 0;
    }

    private function notificar(Cliente $cliente, object $notification): void
    {
        $cliente->loadMissing('user');

        if ($cliente->user?->email) {
            $cliente->user->notify($notification);

            return;
        }

        $destino = new AnonymousNotifiable;
        if ($cliente->email) {
            $destino->route('mail', $cliente->email);
        }
        if ($cliente->telefone) {
            $destino->route('whatsapp', $cliente->telefone);
        }

        $destino->notify($notification);
    }
}
