<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Models\Agendamento;
use App\Models\ComissaoPagamento;
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
     * atendimentos concluídos (valor_total − desconto); taxa vem de manicures.comissao (%).
     * Produtos não entram na base de comissão.
     *
     * Inclui status de liquidação (pago / a pagar) cruzando com comissao_pagamentos
     * do mesmo período exato (datas).
     */
    public function comissoes(int $salaoId, Carbon $inicio, Carbon $fim): Collection
    {
        $agendamentos = Agendamento::with('manicure')
            ->where('salao_id', $salaoId)
            ->where('status', AgendamentoStatus::Concluido->value)
            ->whereBetween('data_hora_inicio', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get();

        $pagos = $this->pagamentosDoPeriodo($salaoId, $inicio, $fim)->keyBy('manicure_id');

        return $agendamentos
            ->groupBy('manicure_id')
            ->map(function (Collection $g) use ($pagos) {
                $manicure = $g->first()->manicure;
                $base = (float) $g->sum(fn ($a) => (float) $a->valor_total - (float) $a->valor_desconto);
                $taxa = (float) ($manicure->comissao ?? 0);
                $comissao = round($base * $taxa / 100, 2);
                $pagamento = $manicure ? $pagos->get($manicure->id) : null;

                return [
                    'manicure_id'  => $manicure?->id,
                    'nome'         => $manicure->nome ?? 'Sem profissional',
                    'foto'         => $manicure?->foto_url,
                    'atendimentos' => $g->count(),
                    'base'         => round($base, 2),
                    'taxa'         => $taxa,
                    'comissao'     => $comissao,
                    'pago'         => $pagamento !== null,
                    'pagamento_id' => $pagamento?->id,
                    'valor_pago'   => $pagamento !== null ? (float) $pagamento->valor : null,
                    'a_pagar'      => $pagamento !== null ? 0.0 : $comissao,
                ];
            })
            ->sortByDesc('base')
            ->values();
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
                'manicure_id' => 'Não há comissão a pagar para este profissional no período.',
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
}
