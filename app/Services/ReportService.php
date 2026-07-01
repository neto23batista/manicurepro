<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        $dataFim    ??= now()->endOfMonth();

        $agendamentos = $this->buscarAgendamentos($salaoId, $dataInicio, $dataFim);

        return [
            'agendamentos' => $agendamentos,
            'resumo'       => $this->calcularResumo($agendamentos),
            'porManicure'  => $this->agruparPorManicure($agendamentos),
            'porServico'   => $this->agruparPorServico($agendamentos),
            'porDia'       => $this->agruparPorDia($agendamentos, $dataInicio, $dataFim),
            'porPagamento' => $this->agruparPorFormaPagamento($agendamentos),
            'dataInicio'   => $dataInicio,
            'dataFim'      => $dataFim,
        ];
    }

    /**
     * Filtros base reutilizáveis.
     */
    protected function buscarAgendamentos(
        ?int $salaoId,
        Carbon $dataInicio,
        Carbon $dataFim
    ): Collection {
        return Agendamento::with(['salao', 'manicure', 'servicos', 'cliente', 'pagamentos'])
            ->whereBetween('data_hora_inicio', [
                $dataInicio->copy()->startOfDay(),
                $dataFim->copy()->endOfDay()
            ])
            ->when($salaoId, fn(Builder $q) => $q->where('salao_id', $salaoId))
            ->orderBy('data_hora_inicio')
            ->get();
    }

    protected function calcularResumo(Collection $agendamentos): array
    {
        $concluidos = $agendamentos->where('status', AgendamentoStatus::Concluido->value);

        return [
            'total'         => $agendamentos->count(),
            'concluidos'    => $concluidos->count(),
            'cancelados'    => $agendamentos->where('status', AgendamentoStatus::Cancelado->value)->count(),
            'nao_compareceu'=> $agendamentos->where('status', AgendamentoStatus::NaoCompareceu->value)->count(),
            'faturamento'   => (float) $concluidos->sum('valor_total'),
            'desconto'      => (float) $concluidos->sum('valor_desconto'),
            'liquido'       => (float) $concluidos->sum(fn($a) => $a->valor_total - $a->valor_desconto),
            'ticket_medio'  => $concluidos->count() > 0
                ? round($concluidos->sum('valor_total') / $concluidos->count(), 2)
                : 0,
        ];
    }

    protected function agruparPorManicure(Collection $agendamentos)
    {
        $concluido = AgendamentoStatus::Concluido->value;

        return $agendamentos
            ->groupBy('manicure_id')
            ->map(function ($ags) use ($concluido) {
                $concluidos = $ags->where('status', $concluido);
                return [
                    'nome'        => $ags->first()->manicure?->nome ?? 'N/A',
                    'foto'        => $ags->first()->manicure?->foto_url,
                    'total'       => $ags->count(),
                    'concluidos'  => $concluidos->count(),
                    'faturamento' => (float) $concluidos->sum('valor_total'),
                    'taxa'        => $ags->count() > 0
                        ? round($concluidos->count() / $ags->count() * 100)
                        : 0,
                ];
            })
            ->sortByDesc('faturamento')
            ->values();
    }

    protected function agruparPorServico(Collection $agendamentos)
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

    protected function agruparPorDia(Collection $agendamentos, Carbon $inicio, Carbon $fim)
    {
        $concluido = AgendamentoStatus::Concluido->value;

        // Agrupa em memória — O(n) em vez de O(n × dias) do loop com filter
        $porData = $agendamentos->groupBy(
            fn($a) => Carbon::parse($a->data_hora_inicio)->toDateString()
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

    protected function agruparPorFormaPagamento(Collection $agendamentos)
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
