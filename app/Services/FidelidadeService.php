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
     * Respeita anti-stacking de cupom promocional e multiplicador do nível.
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
        $cupom = $agendamento->cupom_id
            ? Cupom::find($agendamento->cupom_id)
            : null;

        $creditarPontos = $config?->fidelidade_ativo
            && ! ($cupom?->bloqueiaCreditoFidelidade() ?? false);

        if ($creditarPontos) {
            $pontosBase = (int) floor($valorPago * $config->pontos_por_real);
            $nivel = $this->nivelPara($cliente);
            $mult = (float) ($nivel['multiplicador'] ?? 1.0);
            $pontos = (int) floor($pontosBase * max(1.0, $mult));

            if ($pontos > 0) {
                FidelidadePonto::create([
                    'cliente_id'     => $cliente->id,
                    'salao_id'       => $agendamento->salao_id,
                    'agendamento_id' => $agendamento->id,
                    'pontos'         => $pontos,
                    'tipo'           => 'ganho',
                    'descricao'      => "Atendimento #{$agendamento->id}",
                    'expires_at'     => $this->calcularExpiracao(),
                ]);

                $cliente->increment('pontos_fidelidade', $pontos);
            }
        }

        $this->processarRecompensaIndicacao($cliente->fresh(), $agendamento);
    }

    /**
     * Nível atual com base no total de pontos já ganhos (tipo=ganho).
     *
     * @return array{chave: string, nome: string, pontos_min: int, multiplicador: float}
     */
    public function nivelPara(Cliente $cliente): array
    {
        $niveis = collect(config('manicure.fidelidade.niveis', []))
            ->sortByDesc(fn ($n) => (int) ($n['pontos_min'] ?? 0))
            ->values();

        $ganhos = (int) FidelidadePonto::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'ganho')
            ->sum('pontos');

        foreach ($niveis as $nivel) {
            if ($ganhos >= (int) ($nivel['pontos_min'] ?? 0)) {
                return [
                    'chave'         => (string) ($nivel['chave'] ?? 'bronze'),
                    'nome'          => (string) ($nivel['nome'] ?? 'Bronze'),
                    'pontos_min'    => (int) ($nivel['pontos_min'] ?? 0),
                    'multiplicador' => (float) ($nivel['multiplicador'] ?? 1.0),
                ];
            }
        }

        return [
            'chave'         => 'bronze',
            'nome'          => 'Bronze',
            'pontos_min'    => 0,
            'multiplicador' => 1.0,
        ];
    }

    public function calcularExpiracao(): ?\Carbon\Carbon
    {
        $dias = config('manicure.fidelidade.expiracao_dias');
        if ($dias === null || (int) $dias <= 0) {
            return null;
        }

        return now()->addDays((int) $dias);
    }

    /**
     * Expira pontos vencidos (ganhos com expires_at no passado ainda não debitados).
     * Retorna quantidade de registros processados.
     */
    public function expirarPontosVencidos(): int
    {
        $vencidos = FidelidadePonto::query()
            ->where('tipo', 'ganho')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('pontos', '>', 0)
            ->orderBy('id')
            ->get();

        $processados = 0;

        foreach ($vencidos as $ganho) {
            DB::transaction(function () use ($ganho, &$processados) {
                $travado = FidelidadePonto::whereKey($ganho->id)->lockForUpdate()->first();
                if (! $travado || $travado->tipo !== 'ganho' || $travado->pontos <= 0) {
                    return;
                }
                if (! $travado->expires_at || $travado->expires_at->isFuture()) {
                    return;
                }

                // Já expirado? (registro espelho)
                $jaExpirado = FidelidadePonto::query()
                    ->where('cliente_id', $travado->cliente_id)
                    ->where('tipo', 'expirado')
                    ->where('descricao', 'like', "Expiração #{$travado->id}%")
                    ->exists();

                if ($jaExpirado) {
                    return;
                }

                $cliente = Cliente::whereKey($travado->cliente_id)->lockForUpdate()->first();
                if (! $cliente) {
                    return;
                }

                $debitar = min((int) $travado->pontos, (int) $cliente->pontos_fidelidade);

                FidelidadePonto::create([
                    'cliente_id'     => $cliente->id,
                    'salao_id'       => $travado->salao_id,
                    'agendamento_id' => $travado->agendamento_id,
                    'pontos'         => -$debitar,
                    'tipo'           => 'expirado',
                    'descricao'      => "Expiração #{$travado->id}",
                ]);

                if ($debitar > 0) {
                    $cliente->decrement('pontos_fidelidade', $debitar);
                }

                $processados++;
            });
        }

        return $processados;
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
            'expires_at'     => $this->calcularExpiracao(),
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
            'origem'     => 'indicacao',
            'cliente_id' => $indicador->id,
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

        return (float) (($config !== null ? $config->valor_desconto_pontos : null)
            ?? config('manicure.fidelidade.valor_desconto', 10));
    }

    public function podeResgatar(Cliente $cliente): bool
    {
        return $cliente->pontos_fidelidade >= $this->pontosPorBloco($cliente);
    }

    /**
     * Troca pontos por um cupom de desconto fixo (valor por bloco × blocos).
     * Trava o cliente (lockForUpdate) para impedir resgate duplo concorrente.
     */
    public function resgatar(Cliente $cliente, int $blocos = 1): Cupom
    {
        if ($blocos < 1) {
            throw ValidationException::withMessages([
                'error' => 'Pontos insuficientes para o resgate.',
            ]);
        }

        return DB::transaction(function () use ($cliente, $blocos) {
            $travado = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $custoBloco = $this->pontosPorBloco($travado);
            $valorBloco = $this->valorPorBloco($travado);
            $custoTotal = $custoBloco * $blocos;

            if ($travado->pontos_fidelidade < $custoTotal) {
                throw ValidationException::withMessages([
                    'error' => 'Pontos insuficientes para o resgate.',
                ]);
            }

            $cupom = Cupom::create([
                'salao_id'   => $travado->salao_id,
                'codigo'     => 'FID-'.strtoupper(Str::random(6)),
                'tipo'       => 'fixo',
                'valor'      => $valorBloco * $blocos,
                'uso_maximo' => 1,
                'uso_atual'  => 0,
                'validade'   => now()->addDays(30),
                'ativo'      => true,
                'origem'     => 'fidelidade',
                'cliente_id' => $travado->id,
            ]);

            FidelidadePonto::create([
                'cliente_id'     => $travado->id,
                'salao_id'       => $travado->salao_id,
                'agendamento_id' => null,
                'pontos'         => -$custoTotal,
                'tipo'           => 'resgatado',
                'descricao'      => "Resgate de pontos → cupom {$cupom->codigo}",
            ]);

            $travado->decrement('pontos_fidelidade', $custoTotal);

            return $cupom;
        });
    }
}
