<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Models\Agendamento;
use App\Models\ComissaoAjuste;
use App\Models\ComissaoPagamento;
use App\Models\Despesa;
use App\Models\Manicure;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Caixa (entradas confirmadas) e comissões por profissional para o painel do dono.
 */
class FinanceiroService
{
    /**
     * Entradas de caixa no período: pagamentos confirmados agrupados por forma.
     * A data considerada é a do pagamento (quando o dinheiro entrou).
     *
     * Resgates de vale-presente (forma=voucher) NÃO somam: o dinheiro entrou
     * na VENDA do vale (registrada como pagamento na forma escolhida) — o
     * resgate é só a baixa do crédito. Eles são retornados à parte.
     */
    public function caixa(int $salaoId, Carbon $inicio, Carbon $fim): array
    {
        $pagamentos = Pagamento::where('salao_id', $salaoId)
            ->where('status', PagamentoStatus::Confirmado->value)
            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get();

        $voucher = FormaPagamento::Voucher->value;
        $resgates = $pagamentos->where('forma', $voucher);
        $entradas = $pagamentos->where('forma', '!=', $voucher);

        $porForma = $entradas
            ->groupBy('forma')
            ->map(fn (Collection $g, $forma) => [
                'forma' => $forma,
                'label' => Pagamento::FORMAS_LABELS[$forma] ?? ucfirst((string) $forma),
                'total' => (float) $g->sum('valor'),
                'count' => $g->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'total'             => (float) $entradas->sum('valor'),
            'count'             => $entradas->count(),
            'porForma'          => $porForma,
            'resgatesVale'      => (float) $resgates->sum('valor'),
            'resgatesValeCount' => $resgates->count(),
        ];
    }

    /**
     * Comissão por profissional no período. Base = serviços líquidos dos
     * atendimentos concluídos (valor_total − desconto).
     *
     * Taxa: por linha de serviço usa comissao_fixo (R$) ou comissao_percentual (%)
     * do serviço quando definidos; senão cai na % da manicure. Sem serviços
     * vinculados, mantém o cálculo flat (manicure %). Produtos não entram.
     *
     * Ajustes manuais do período (comissao_ajustes) somam/subtraem do total.
     * Inclui status de liquidação (pago / a pagar) cruzando com comissao_pagamentos
     * do mesmo período exato (datas).
     */
    public function comissoes(int $salaoId, Carbon $inicio, Carbon $fim): Collection
    {
        $agendamentos = Agendamento::with(['manicure', 'servicos'])
            ->where('salao_id', $salaoId)
            ->where('status', AgendamentoStatus::Concluido->value)
            ->whereBetween('data_hora_inicio', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get();

        $pagos = $this->pagamentosDoPeriodo($salaoId, $inicio, $fim)->keyBy('manicure_id');
        $ajustesPorManicure = $this->ajustesDoPeriodo($salaoId, $inicio, $fim)
            ->groupBy('manicure_id');

        $linhas = $agendamentos
            ->groupBy('manicure_id')
            ->map(function (Collection $g) use ($pagos, $ajustesPorManicure) {
                $manicure = $g->first()->manicure;
                $taxaPadrao = (float) ($manicure->comissao ?? 0);
                $base = 0.0;
                $comissaoCalc = 0.0;
                $usaRegraServico = false;

                foreach ($g as $agendamento) {
                    $linha = $this->calcularComissaoAgendamento($agendamento, $taxaPadrao);
                    $base += $linha['base'];
                    $comissaoCalc += $linha['comissao'];
                    $usaRegraServico = $usaRegraServico || $linha['usa_regra_servico'];
                }

                $ajustes = $manicure
                    ? $ajustesPorManicure->get($manicure->id, collect())
                    : collect();
                $ajuste = round((float) $ajustes->sum('valor'), 2);
                $comissao = round($comissaoCalc + $ajuste, 2);
                $taxa = $base > 0.0
                    ? round($comissaoCalc / $base * 100, 2)
                    : $taxaPadrao;
                $pagamento = $manicure ? $pagos->get($manicure->id) : null;

                return [
                    'manicure_id'       => $manicure?->id,
                    'nome'              => $manicure->nome ?? 'Sem profissional',
                    'foto'              => $manicure?->foto_url,
                    'atendimentos'      => $g->count(),
                    'base'              => round($base, 2),
                    'taxa'              => $taxa,
                    'taxa_padrao'       => $taxaPadrao,
                    'usa_regra_servico' => $usaRegraServico,
                    'comissao_calc'     => round($comissaoCalc, 2),
                    'ajuste'            => $ajuste,
                    'comissao'          => $comissao,
                    'ajustes'           => $ajustes->values(),
                    'pago'              => $pagamento !== null,
                    'pagamento_id'      => $pagamento?->id,
                    'valor_pago'        => $pagamento !== null ? (float) $pagamento->valor : null,
                    'a_pagar'           => $pagamento !== null ? 0.0 : $comissao,
                ];
            });

        // Manicures só com ajuste (sem atendimento no período) também aparecem.
        foreach ($ajustesPorManicure as $manicureId => $ajustes) {
            if ($linhas->has($manicureId)) {
                continue;
            }
            $manicure = $ajustes->first()->manicure;
            $ajuste = round((float) $ajustes->sum('valor'), 2);
            $taxaPadrao = (float) ($manicure->comissao ?? 0);
            $pagamento = $pagos->get((int) $manicureId);

            $linhas->put($manicureId, [
                'manicure_id'       => (int) $manicureId,
                'nome'              => $manicure->nome ?? 'Sem profissional',
                'foto'              => $manicure?->foto_url,
                'atendimentos'      => 0,
                'base'              => 0.0,
                'taxa'              => $taxaPadrao,
                'taxa_padrao'       => $taxaPadrao,
                'usa_regra_servico' => false,
                'comissao_calc'     => 0.0,
                'ajuste'            => $ajuste,
                'comissao'          => $ajuste,
                'ajustes'           => $ajustes->values(),
                'pago'              => $pagamento !== null,
                'pagamento_id'      => $pagamento?->id,
                'valor_pago'        => $pagamento !== null ? (float) $pagamento->valor : null,
                'a_pagar'           => $pagamento !== null ? 0.0 : $ajuste,
            ]);
        }

        return $linhas
            ->sortByDesc('base')
            ->values();
    }

    /**
     * Calcula base e comissão de um atendimento concluído.
     * Sem serviços no pivot → flat (valor líquido × % manicure).
     *
     * @return array{base: float, comissao: float, usa_regra_servico: bool}
     */
    public function calcularComissaoAgendamento(Agendamento $agendamento, ?float $taxaPadrao = null): array
    {
        $manicure = $agendamento->manicure;
        $taxaPadrao ??= (float) ($manicure->comissao ?? 0);
        $liquido = max(0.0, (float) $agendamento->valor_total - (float) $agendamento->valor_desconto);

        $servicos = $agendamento->relationLoaded('servicos')
            ? $agendamento->servicos
            : $agendamento->servicos()->get();

        if ($servicos->isEmpty()) {
            return [
                'base'              => round($liquido, 2),
                'comissao'          => round($liquido * $taxaPadrao / 100, 2),
                'usa_regra_servico' => false,
            ];
        }

        $somaPrecos = (float) $servicos->sum(fn ($s) => (float) $s->pivot->preco);
        $desconto = (float) $agendamento->valor_desconto;
        $base = 0.0;
        $comissao = 0.0;
        $usaRegra = false;

        foreach ($servicos as $servico) {
            $preco = (float) $servico->pivot->preco;
            $parteDesconto = $somaPrecos > 0 ? $desconto * ($preco / $somaPrecos) : 0.0;
            $baseLinha = max(0.0, $preco - $parteDesconto);
            $base += $baseLinha;

            if ($servico->comissao_fixo !== null) {
                $comissao += (float) $servico->comissao_fixo;
                $usaRegra = true;
            } elseif ($servico->comissao_percentual !== null) {
                $comissao += $baseLinha * ((float) $servico->comissao_percentual) / 100;
                $usaRegra = true;
            } else {
                $comissao += $baseLinha * $taxaPadrao / 100;
            }
        }

        return [
            'base'              => round($base, 2),
            'comissao'          => round($comissao, 2),
            'usa_regra_servico' => $usaRegra,
        ];
    }

    /**
     * Ajustes manuais (+/−) do período exato (match de datas).
     */
    public function ajustesDoPeriodo(int $salaoId, Carbon $inicio, Carbon $fim): Collection
    {
        return ComissaoAjuste::with(['manicure', 'user'])
            ->where('salao_id', $salaoId)
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->whereDate('periodo_fim', $fim->toDateString())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Registra ajuste manual de comissão (+/−) com auditoria.
     */
    public function registrarAjuste(
        int $salaoId,
        int $manicureId,
        Carbon $inicio,
        Carbon $fim,
        float $valor,
        ?string $motivo = null,
        ?int $userId = null,
    ): ComissaoAjuste {
        $manicure = Manicure::where('salao_id', $salaoId)->find($manicureId);
        if ($manicure === null) {
            throw ValidationException::withMessages([
                'manicure_id' => 'Profissional não pertence a este salão.',
            ]);
        }

        $valor = round($valor, 2);
        if ($valor == 0.0) {
            throw ValidationException::withMessages([
                'valor' => 'Informe um valor diferente de zero.',
            ]);
        }

        $ajuste = ComissaoAjuste::create([
            'salao_id'       => $salaoId,
            'manicure_id'    => $manicureId,
            'periodo_inicio' => $inicio->copy()->startOfDay()->toDateString(),
            'periodo_fim'    => $fim->copy()->endOfDay()->toDateString(),
            'valor'          => $valor,
            'motivo'         => $motivo,
            'user_id'        => $userId,
        ]);

        AuditLogger::log('comissao.ajuste', $ajuste, [
            'salao_id'       => $salaoId,
            'manicure_id'    => $manicureId,
            'periodo_inicio' => $ajuste->periodo_inicio->toDateString(),
            'periodo_fim'    => $ajuste->periodo_fim->toDateString(),
            'valor'          => $valor,
            'motivo'         => $motivo,
        ]);

        return $ajuste;
    }

    /**
     * Remove ajuste manual, com checagem de salão e auditoria.
     */
    public function removerAjuste(ComissaoAjuste $ajuste, int $salaoId): void
    {
        if ((int) $ajuste->salao_id !== $salaoId) {
            throw ValidationException::withMessages([
                'ajuste' => 'Ajuste não pertence a este salão.',
            ]);
        }

        AuditLogger::log('comissao.ajuste_removido', $ajuste, [
            'salao_id'    => $ajuste->salao_id,
            'manicure_id' => $ajuste->manicure_id,
            'valor'       => (float) $ajuste->valor,
            'motivo'      => $ajuste->motivo,
        ]);

        $ajuste->delete();
    }

    /**
     * Repasses de comissão já registrados no período (match exato das datas).
     */
    public function pagamentosDoPeriodo(int $salaoId, Carbon $inicio, Carbon $fim): Collection
    {
        return ComissaoPagamento::with(['manicure', 'user'])
            ->where('salao_id', $salaoId)
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->whereDate('periodo_fim', $fim->toDateString())
            ->orderByDesc('pago_em')
            ->get();
    }

    /**
     * Histórico recente de repasses do salão (independente do filtro de período).
     */
    public function historicoPagamentos(int $salaoId, int $limite = 20): Collection
    {
        return ComissaoPagamento::with(['manicure', 'user'])
            ->where('salao_id', $salaoId)
            ->orderByDesc('pago_em')
            ->limit($limite)
            ->get();
    }

    /**
     * Marca a comissão do profissional no período como paga (repasse).
     * Valor = comissão calculada; impede duplicata no mesmo período.
     */
    public function marcarPago(
        int $salaoId,
        int $manicureId,
        Carbon $inicio,
        Carbon $fim,
        ?int $userId = null,
        ?string $observacao = null,
    ): ComissaoPagamento {
        $manicure = Manicure::where('salao_id', $salaoId)->find($manicureId);
        if ($manicure === null) {
            throw ValidationException::withMessages([
                'manicure_id' => 'Profissional não pertence a este salão.',
            ]);
        }

        $periodoInicio = $inicio->copy()->startOfDay();
        $periodoFim = $fim->copy()->endOfDay();

        $jaPago = ComissaoPagamento::where('salao_id', $salaoId)
            ->where('manicure_id', $manicureId)
            ->whereDate('periodo_inicio', $periodoInicio->toDateString())
            ->whereDate('periodo_fim', $periodoFim->toDateString())
            ->exists();

        if ($jaPago) {
            throw ValidationException::withMessages([
                'manicure_id' => 'Comissão deste profissional neste período já foi marcada como paga.',
            ]);
        }

        $linha = $this->comissoes($salaoId, $periodoInicio, $periodoFim)
            ->firstWhere('manicure_id', $manicureId);

        $valor = (float) ($linha['comissao'] ?? 0);
        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'manicure_id' => 'Não há comissão a pagar para este profissional no período (após ajustes).',
            ]);
        }

        return ComissaoPagamento::create([
            'salao_id'       => $salaoId,
            'manicure_id'    => $manicureId,
            'periodo_inicio' => $periodoInicio->toDateString(),
            'periodo_fim'    => $periodoFim->toDateString(),
            'valor'          => $valor,
            'pago_em'        => now(),
            'observacao'     => $observacao,
            'user_id'        => $userId,
        ]);
    }

    /**
     * Remove um repasse (desfaz "marcar como pago"), com checagem de salão.
     */
    public function desfazerPagamento(ComissaoPagamento $pagamento, int $salaoId): void
    {
        if ((int) $pagamento->salao_id !== $salaoId) {
            throw ValidationException::withMessages([
                'pagamento' => 'Repasse não pertence a este salão.',
            ]);
        }

        $pagamento->delete();
    }

    /**
     * Fluxo de caixa do período: entradas (pagamentos confirmados, sem resgates de vale)
     * vs saídas (despesas pagas + comissões repassadas). Inclui pendências a vencer.
     *
     * @return array{
     *     entradas: float,
     *     saidas: float,
     *     saldo: float,
     *     despesas_pagas: float,
     *     comissoes_pagas: float,
     *     despesas_pendentes: float,
     *     despesas_pendentes_count: int
     * }
     */
    public function fluxoCaixa(int $salaoId, Carbon $inicio, Carbon $fim): array
    {
        $ini = $inicio->copy()->startOfDay();
        $fimDia = $fim->copy()->endOfDay();

        $entradas = (float) Pagamento::where('salao_id', $salaoId)
            ->where('status', PagamentoStatus::Confirmado->value)
            ->where('forma', '!=', FormaPagamento::Voucher->value)
            ->whereBetween('created_at', [$ini, $fimDia])
            ->sum('valor');

        $despesasPagas = (float) Despesa::where('salao_id', $salaoId)
            ->whereNotNull('pago_em')
            ->whereBetween('pago_em', [$ini, $fimDia])
            ->sum('valor');

        $comissoesPagas = (float) ComissaoPagamento::where('salao_id', $salaoId)
            ->whereBetween('pago_em', [$ini, $fimDia])
            ->sum('valor');

        $pendentesQuery = Despesa::where('salao_id', $salaoId)
            ->pendentes()
            ->whereDate('vencimento', '>=', $ini->toDateString())
            ->whereDate('vencimento', '<=', $fimDia->toDateString());

        $despesasPendentes = (float) (clone $pendentesQuery)->sum('valor');
        $despesasPendentesCount = (int) (clone $pendentesQuery)->count();

        $saidas = round($despesasPagas + $comissoesPagas, 2);
        $entradas = round($entradas, 2);

        return [
            'entradas'                 => $entradas,
            'saidas'                   => $saidas,
            'saldo'                    => round($entradas - $saidas, 2),
            'despesas_pagas'           => round($despesasPagas, 2),
            'comissoes_pagas'          => round($comissoesPagas, 2),
            'despesas_pendentes'       => round($despesasPendentes, 2),
            'despesas_pendentes_count' => $despesasPendentesCount,
        ];
    }
}
