<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Salao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ReportService
{
    /**
     * Gera dados consolidados para relatório de período.
     */
    public function gerarRelatorio(
        ?int $salaoId = null,
        ?Carbon $dataInicio = null,
        ?Carbon $dataFim = null
    ): array {
        $dataInicio ??= now()->startOfMonth();
        $dataFim ??= now()->endOfMonth();

        $salao = $salaoId ? Salao::find($salaoId) : null;
        $agendamentos = $this->buscarAgendamentos($salaoId, $dataInicio, $dataFim);
        $porManicure = $this->agruparPorManicure($agendamentos);
        $resumo = $this->calcularResumo($agendamentos, $porManicure);

        return [
            'agendamentos' => $agendamentos,
            'resumo'       => $resumo,
            'porManicure'  => $porManicure,
            'porServico'   => $this->agruparPorServico($agendamentos),
            'porDia'       => $this->agruparPorDia($agendamentos, $dataInicio, $dataFim),
            'porPagamento' => $this->agruparPorFormaPagamento($agendamentos),
            'salao'        => $salao,
            'salaoNome'    => $salao !== null ? $salao->nome : 'Todos os salões',
            'dataInicio'   => $dataInicio,
            'dataFim'      => $dataFim,
        ];
    }

    /**
     * Filtros base reutilizáveis.
     *
     * @return Collection<int, Agendamento>
     */
    protected function buscarAgendamentos(
        ?int $salaoId,
        Carbon $dataInicio,
        Carbon $dataFim
    ): Collection {
        return Agendamento::with(['salao', 'manicure', 'servicos', 'cliente', 'pagamentos'])
            ->whereBetween('data_hora_inicio', [
                $dataInicio->copy()->startOfDay(),
                $dataFim->copy()->endOfDay(),
            ])
            ->when($salaoId, fn (Builder $q) => $q->where('salao_id', $salaoId))
            ->orderBy('data_hora_inicio')
            ->get();
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     */
    protected function calcularResumo(Collection $agendamentos, SupportCollection $porManicure): array
    {
        $concluidos = $agendamentos->where('status', AgendamentoStatus::Concluido->value);
        $liquido = (float) $concluidos->sum(fn (Agendamento $a) => $a->valor_total - $a->valor_desconto);

        return [
            'total'          => $agendamentos->count(),
            'concluidos'     => $concluidos->count(),
            'cancelados'     => $agendamentos->where('status', AgendamentoStatus::Cancelado->value)->count(),
            'nao_compareceu' => $agendamentos->where('status', AgendamentoStatus::NaoCompareceu->value)->count(),
            'faturamento'    => (float) $concluidos->sum('valor_total'),
            'desconto'       => (float) $concluidos->sum('valor_desconto'),
            'liquido'        => $liquido,
            'ticket_medio'   => $concluidos->count() > 0
                ? round($liquido / $concluidos->count(), 2)
                : 0,
            'comissoes'     => (float) $porManicure->sum('comissao'),
            'base_comissao' => (float) $porManicure->sum('base'),
        ];
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     */
    protected function agruparPorManicure(Collection $agendamentos): SupportCollection
    {
        $concluido = AgendamentoStatus::Concluido->value;

        return $agendamentos
            ->groupBy('manicure_id')
            ->map(function ($ags) use ($concluido) {
                $concluidos = $ags->where('status', $concluido);
                $primeiro = $ags->first();
                $manicure = $primeiro->manicure;
                $base = (float) $concluidos->sum(fn (Agendamento $a) => (float) $a->valor_total - (float) $a->valor_desconto);
                $taxaComissao = (float) ($manicure !== null ? $manicure->comissao : 0);

                return [
                    'nome'          => $manicure !== null ? $manicure->nome : 'N/A',
                    'foto'          => $manicure?->foto_url,
                    'total'         => $ags->count(),
                    'concluidos'    => $concluidos->count(),
                    'faturamento'   => (float) $concluidos->sum('valor_total'),
                    'base'          => round($base, 2),
                    'taxa_comissao' => $taxaComissao,
                    'comissao'      => round($base * $taxaComissao / 100, 2),
                    'taxa'          => $ags->count() > 0
                        ? round($concluidos->count() / $ags->count() * 100)
                        : 0,
                ];
            })
            ->sortByDesc('faturamento')
            ->values();
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     */
    protected function agruparPorServico(Collection $agendamentos): SupportCollection
    {
        $contagem = [];
        foreach ($agendamentos->where('status', AgendamentoStatus::Concluido->value) as $ag) {
            foreach ($ag->servicos as $servico) {
                $id = $servico->id;
                $contagem[$id] ??= [
                    'nome'        => $servico->nome,
                    'quantidade'  => 0,
                    'faturamento' => 0,
                ];
                $contagem[$id]['quantidade']++;
                $contagem[$id]['faturamento'] += (float) ($servico->pivot->preco ?? $servico->preco);
            }
        }

        return collect($contagem)->sortByDesc('quantidade')->values();
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     */
    protected function agruparPorDia(Collection $agendamentos, Carbon $inicio, Carbon $fim): SupportCollection
    {
        $concluido = AgendamentoStatus::Concluido->value;

        // Agrupa em memória — O(n) em vez de O(n × dias) do loop com filter
        $porData = $agendamentos->groupBy(
            fn (Agendamento $a) => Carbon::parse($a->data_hora_inicio)->toDateString(),
        );

        $dias = collect();
        $cursor = $inicio->copy()->startOfDay();
        while ($cursor->lte($fim)) {
            $doDia = $porData->get($cursor->toDateString(), collect());
            $dias->push([
                'data'        => $cursor->copy(),
                'total'       => $doDia->count(),
                'faturamento' => (float) $doDia->where('status', $concluido)->sum('valor_total'),
            ]);
            $cursor->addDay();
        }

        return $dias;
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     */
    protected function agruparPorFormaPagamento(Collection $agendamentos): SupportCollection
    {
        $contagem = [];
        foreach ($agendamentos->where('status', AgendamentoStatus::Concluido->value) as $ag) {
            foreach ($ag->pagamentos as $pag) {
                $forma = $pag->forma;
                $contagem[$forma] ??= ['forma' => $forma, 'total' => 0, 'count' => 0];
                $contagem[$forma]['total'] += (float) $pag->valor;
                $contagem[$forma]['count']++;
            }
        }

        return collect($contagem)->sortByDesc('total')->values();
    }
}
