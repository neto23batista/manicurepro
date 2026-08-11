<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\FidelidadePonto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        if (! $cliente) {
            return;
        }

        $cliente->increment('total_visitas');
        $cliente->increment('total_gasto', $valorPago);

        $config = $agendamento->salao->configuracao;
        if ($config?->fidelidade_ativo) {
            $pontos = (int) floor($valorPago * $config->pontos_por_real);
            if ($pontos > 0) {
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
        }

        $this->processarRecompensaIndicacao($cliente->fresh(), $agendamento);
    }

    /**
     * Na primeira visita concluída de um cliente indicado, recompensa o indicador
     * com pontos ou cupom (conforme config manicure.indicacao).
     */
    public function processarRecompensaIndicacao(Cliente $indicado, Agendamento $agendamento): void
    {
        if (! config('manicure.indicacao.enabled', true)) {
            return;
        }

        if (! $indicado->indicado_por_cliente_id) {
            return;
        }

        $concluidos = Agendamento::query()
            ->where('cliente_id', $indicado->id)
            ->where('status', AgendamentoStatus::Concluido->value)
            ->count();

        if ($concluidos !== 1) {
            return;
        }

        $indicador = Cliente::find($indicado->indicado_por_cliente_id);
        if (! $indicador || $indicador->id === $indicado->id) {
            return;
        }

        $descricaoBase = "Indicação #{$indicado->id}";

        $jaRecompensado = FidelidadePonto::query()
            ->where('cliente_id', $indicador->id)
            ->where('descricao', 'like', $descricaoBase.'%')
            ->exists();

        if ($jaRecompensado) {
            return;
        }

        $modo = config('manicure.indicacao.recompensa', 'pontos');

        if ($modo === 'cupom') {
            $this->gerarCupomIndicacao($indicador, $indicado, $agendamento, $descricaoBase);

            return;
        }

        $pontos = (int) config('manicure.indicacao.pontos', 50);
        if ($pontos <= 0) {
            return;
        }

        FidelidadePonto::create([
            'cliente_id'     => $indicador->id,
            'salao_id'       => $indicador->salao_id,
            'agendamento_id' => $agendamento->id,
            'pontos'         => $pontos,
            'tipo'           => 'ganho',
            'descricao'      => "{$descricaoBase} — {$indicado->nome}",
        ]);

        $indicador->increment('pontos_fidelidade', $pontos);
    }

    private function gerarCupomIndicacao(
        Cliente $indicador,
        Cliente $indicado,
        Agendamento $agendamento,
        string $descricaoBase
    ): Cupom {
        $valor = (float) config('manicure.indicacao.cupom_valor', 20);
        $validadeDias = (int) config('manicure.indicacao.cupom_validade_dias', 30);

        $cupom = Cupom::create([
            'salao_id'   => $indicador->salao_id,
            'codigo'     => 'IND-'.strtoupper(Str::random(6)),
            'tipo'       => 'fixo',
            'valor'      => $valor,
            'uso_maximo' => 1,
            'uso_atual'  => 0,
            'validade'   => now()->addDays($validadeDias),
            'ativo'      => true,
        ]);

        FidelidadePonto::create([
            'cliente_id'     => $indicador->id,
            'salao_id'       => $indicador->salao_id,
            'agendamento_id' => $agendamento->id,
            'pontos'         => 0,
            'tipo'           => 'ajuste',
            'descricao'      => "{$descricaoBase} — cupom {$cupom->codigo}",
        ]);

        return $cupom;
    }

    public function pontosPorBloco(Cliente $cliente): int
    {
        $config = $cliente->salao?->configuracao;

        return (int) (($config !== null ? $config->pontos_para_desconto : null)
            ?? config('manicure.fidelidade.pontos_para_desconto', 100));
    }

    public function valorPorBloco(Cliente $cliente): float
    {
        $config = $cliente->salao?->configuracao;

        return (float) (($config !== null ? $config->valor_desconto : null)
            ?? config('manicure.fidelidade.valor_desconto', 10));
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
            throw ValidationException::withMessages([
                'error' => 'Pontos insuficientes para o resgate.',
            ]);
        }

        return DB::transaction(function () use ($cliente, $blocos, $custoTotal, $valorBloco) {
            $cupom = Cupom::create([
                'salao_id'   => $cliente->salao_id,
                'codigo'     => 'FID-'.strtoupper(Str::random(6)),
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
