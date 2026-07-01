<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Enums\PagamentoStatus;
use App\Models\Agendamento;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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

        $voucher  = \App\Enums\FormaPagamento::Voucher->value;
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
     */
    public function comissoes(int $salaoId, Carbon $inicio, Carbon $fim): Collection
    {
        $agendamentos = Agendamento::with('manicure')
            ->where('salao_id', $salaoId)
            ->where('status', AgendamentoStatus::Concluido->value)
            ->whereBetween('data_hora_inicio', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get();

        return $agendamentos
            ->groupBy('manicure_id')
            ->map(function (Collection $g) {
                $manicure = $g->first()->manicure;
                $base = (float) $g->sum(fn ($a) => (float) $a->valor_total - (float) $a->valor_desconto);
                $taxa = (float) ($manicure->comissao ?? 0);

                return [
                    'manicure_id'  => $manicure?->id,
                    'nome'         => $manicure?->nome ?? 'Sem profissional',
                    'foto'         => $manicure?->foto_url,
                    'atendimentos' => $g->count(),
                    'base'         => round($base, 2),
                    'taxa'         => $taxa,
                    'comissao'     => round($base * $taxa / 100, 2),
                ];
            })
            ->sortByDesc('base')
            ->values();
    }
}
