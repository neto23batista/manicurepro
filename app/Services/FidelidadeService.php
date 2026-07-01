<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\FidelidadePonto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encapsula a lógica de pontos de fidelidade.
 * Extraído do AgendaService::finalizarAtendimento.
 */
class FidelidadeService
{
    /**
     * Credita pontos ao cliente conforme configuração do salão.
     * Atualiza contadores do cliente (visitas/gasto) também.
     */
    public function creditarPorAtendimento(Agendamento $agendamento, float $valorPago): void
    {
        $cliente = $agendamento->cliente;
        if (!$cliente) {
            return;
        }

        $cliente->increment('total_visitas');
        $cliente->increment('total_gasto', $valorPago);

        $config = $agendamento->salao->configuracao;
        if (!$config?->fidelidade_ativo) {
            return;
        }

        $pontos = (int) floor($valorPago * $config->pontos_por_real);
        if ($pontos <= 0) {
            return;
        }

        FidelidadePonto::create([
            'cliente_id'     => $cliente->id,
            'salao_id'       => $agendamento->salao_id,
            'agendamento_id' => $agendamento->id,
            'pontos'         => $pontos,
            'tipo'           => 'ganho',
            'descricao'      => "Atendimento #{$agendamento->id}",
        ]);

        $cliente->increment('pontos_fidelidade', $pontos);
    }

    public function pontosPorBloco(Cliente $cliente): int
    {
        $config = $cliente->salao?->configuracao;
        return (int) ($config?->pontos_para_desconto ?? config('manicure.fidelidade.pontos_para_desconto', 100));
    }

    public function valorPorBloco(Cliente $cliente): float
    {
        $config = $cliente->salao?->configuracao;
        return (float) ($config?->valor_desconto ?? config('manicure.fidelidade.valor_desconto', 10));
    }

    public function podeResgatar(Cliente $cliente): bool
    {
        return $cliente->pontos_fidelidade >= $this->pontosPorBloco($cliente);
    }

    /**
     * Troca pontos por um cupom de desconto fixo (valor por bloco × blocos).
     */
    public function resgatar(Cliente $cliente, int $blocos = 1): Cupom
    {
        $custoBloco = $this->pontosPorBloco($cliente);
        $valorBloco = $this->valorPorBloco($cliente);
        $custoTotal = $custoBloco * $blocos;

        if ($blocos < 1 || $cliente->pontos_fidelidade < $custoTotal) {
            throw new \RuntimeException('Pontos insuficientes para o resgate.');
        }

        return DB::transaction(function () use ($cliente, $blocos, $custoTotal, $valorBloco) {
            $cupom = Cupom::create([
                'salao_id'   => $cliente->salao_id,
                'codigo'     => 'FID-' . strtoupper(Str::random(6)),
                'tipo'       => 'fixo',
                'valor'      => $valorBloco * $blocos,
                'uso_maximo' => 1,
                'uso_atual'  => 0,
                'validade'   => now()->addDays(30),
                'ativo'      => true,
            ]);

            FidelidadePonto::create([
                'cliente_id'     => $cliente->id,
                'salao_id'       => $cliente->salao_id,
                'agendamento_id' => null,
                'pontos'         => -$custoTotal,
                'tipo'           => 'resgatado',
                'descricao'      => "Resgate de pontos → cupom {$cupom->codigo}",
            ]);

            $cliente->decrement('pontos_fidelidade', $custoTotal);

            return $cupom;
        });
    }
}
