<?php

namespace App\Repositories;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\ClienteSegmentacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsula todas as queries de métricas/estatísticas dos dashboards.
 * Centraliza lógica que antes vivia espalhada nos controllers.
 */
class DashboardRepository
{
    // ===== ADMIN =====

    public function adminTotais(): array
    {
        return [
            'totalSaloes'    => Salao::count(),
            'totalManicures' => Manicure::count(),
            'totalClientes'  => Cliente::count(),
            'totalUsers'     => User::count(),
        ];
    }

    public function adminAgendamentosResumo(): array
    {
        return [
            'agendamentosHoje' => Agendamento::hoje()->count(),
            'agendamentosMes'  => Agendamento::doMes()->count(),
            'faturamentoMes'   => (float) Agendamento::concluidos()->doMes()->sum('valor_total'),
        ];
    }

    public function topSaloes(int $limit = 5)
    {
        return Salao::withCount(['agendamentos' => fn ($q) => $q->whereMonth('data_hora_inicio', now()->month),
        ])
            ->withAvg('avaliacoes as nota_media_calc', 'nota')
            ->orderByDesc('agendamentos_count')
            ->take($limit)
            ->get();
    }

    public function agendamentosRecentes(int $limit = 10)
    {
        return Agendamento::with(['salao', 'manicure', 'cliente'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function dadosMeses(int $meses = 6): Collection
    {
        $inicio = now()->subMonths($meses - 1)->startOfMonth();
        $concluido = AgendamentoStatus::Concluido->value;

        // Expressão de "ano-mês" portável entre MySQL e SQLite
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $anoMesExpr = $isSqlite
            ? "strftime('%Y-%m', data_hora_inicio)"
            : "DATE_FORMAT(data_hora_inicio, '%Y-%m')";

        // 1 query agregada em vez de 2 queries por mês
        $rows = Agendamento::query()
            ->selectRaw("{$anoMesExpr} AS ano_mes")
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN valor_total ELSE 0 END), 0) AS faturamento', [$concluido])
            ->where('data_hora_inicio', '>=', $inicio)
            ->groupBy('ano_mes')
            ->get()
            ->keyBy('ano_mes');

        return collect(range($meses - 1, 0))->reverse()->map(function ($i) use ($rows) {
            $mes = now()->subMonths($i);
            $row = $rows->get($mes->format('Y-m'));

            return [
                'mes'         => $mes->format('M/Y'),
                'total'       => (int) ($row->total ?? 0),
                'faturamento' => (float) ($row->faturamento ?? 0),
            ];
        })->values();
    }

    // ===== DONO =====

    /** Eager-load cliente (total_faltas column powers eh_risco_no_show). */
    private function withClienteFaltas(): array
    {
        return [
            'manicure',
            'servicos',
            'cliente',
        ];
    }

    public function donoResumoHoje(Salao $salao): array
    {
        $hojeAgs = $salao->agendamentos()
            ->whereDate('data_hora_inicio', today())
            ->with($this->withClienteFaltas())
            ->orderBy('data_hora_inicio')
            ->get();

        $concluido = AgendamentoStatus::Concluido->value;
        $riscoHoje = $hojeAgs
            ->filter(fn ($ag) => $ag->cliente?->eh_risco_no_show)
            ->unique('cliente_id')
            ->values();

        return [
            'agendamentosHoje'  => $hojeAgs,
            'totalHoje'         => $hojeAgs->count(),
            'concluidosHoje'    => $hojeAgs->where('status', $concluido)->count(),
            'faturamentoHoje'   => (float) $hojeAgs->where('status', $concluido)->sum('valor_total'),
            'clientesRiscoHoje' => $riscoHoje,
        ];
    }

    public function donoResumoMes(Salao $salao): array
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();
        $inicioAnt = now()->subMonthNoOverflow()->startOfMonth();
        $fimAnt = now()->subMonthNoOverflow()->endOfMonth();

        $totalMes = $salao->agendamentos()
            ->whereBetween('data_hora_inicio', [$inicioMes, $fimMes])
            ->count();
        $totalMesAnterior = $salao->agendamentos()
            ->whereBetween('data_hora_inicio', [$inicioAnt, $fimAnt])
            ->count();

        $faturamentoMes = (float) $salao->agendamentos()
            ->concluidos()
            ->whereBetween('data_hora_inicio', [$inicioMes, $fimMes])
            ->sum('valor_total');
        $faturamentoMesAnterior = (float) $salao->agendamentos()
            ->concluidos()
            ->whereBetween('data_hora_inicio', [$inicioAnt, $fimAnt])
            ->sum('valor_total');

        $novosClientesMes = $salao->clientes()
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->count();
        $novosClientesMesAnterior = $salao->clientes()
            ->whereBetween('created_at', [$inicioAnt, $fimAnt])
            ->count();

        return [
            'totalMes'                    => $totalMes,
            'totalMesAnterior'            => $totalMesAnterior,
            'deltaAgendamentosPct'        => $this->deltaPercentual($totalMes, $totalMesAnterior),
            'faturamentoMes'              => $faturamentoMes,
            'faturamentoMesAnterior'      => $faturamentoMesAnterior,
            'deltaFaturamentoPct'         => $this->deltaPercentual($faturamentoMes, $faturamentoMesAnterior),
            'totalClientes'               => $salao->clientes()->count(),
            'novosClientesMes'            => $novosClientesMes,
            'novosClientesMesAnterior'    => $novosClientesMesAnterior,
            'deltaNovosClientesPct'       => $this->deltaPercentual($novosClientesMes, $novosClientesMesAnterior),
        ];
    }

    /**
     * Alertas de negócio para o dashboard (além de no-show/estoque já exibidos).
     *
     * @return list<array{tipo: string, titulo: string, mensagem: string, url: ?string, url_label: ?string}>
     */
    public function donoAlertasNegocio(Salao $salao, ClienteSegmentacao $crm): array
    {
        $alertas = [];

        $inativos = Cliente::query()
            ->where('salao_id', $salao->id)
            ->where('ativo', true);
        $crm->aplicarFiltro($inativos, 'inativo');
        $qtdInativos = $inativos->count();

        if ($qtdInativos > 0) {
            $alertas[] = [
                'tipo'      => 'secondary',
                'titulo'    => 'Clientes inativos',
                'mensagem'  => $qtdInativos.' '
                    .($qtdInativos === 1 ? 'cliente sem visita recente' : 'clientes sem visita recente')
                    .'. Considere a campanha de reativação.',
                'url'       => route('dono.clientes.index', ['segmento' => 'inativo']),
                'url_label' => 'Ver inativos',
            ];
        }

        $risco = Cliente::query()
            ->where('salao_id', $salao->id)
            ->where('ativo', true);
        $crm->aplicarFiltro($risco, 'risco_churn');
        $qtdRisco = $risco->count();

        if ($qtdRisco > 0) {
            $alertas[] = [
                'tipo'      => 'danger',
                'titulo'    => 'Risco de churn',
                'mensagem'  => $qtdRisco.' '
                    .($qtdRisco === 1 ? 'cliente esfriando' : 'clientes esfriando')
                    .' (última visita na janela de risco).',
                'url'       => route('dono.clientes.index', ['segmento' => 'risco_churn']),
                'url_label' => 'Ver risco churn',
            ];
        }

        $inicioSemana = now()->startOfWeek();
        $fimSemana = now()->endOfWeek();
        $inicioSemanaAnt = now()->subWeek()->startOfWeek();
        $fimSemanaAnt = now()->subWeek()->endOfWeek();
        $cancelado = AgendamentoStatus::Cancelado->value;

        $cancelamentosSemana = $salao->agendamentos()
            ->where('status', $cancelado)
            ->whereBetween('data_hora_inicio', [$inicioSemana, $fimSemana])
            ->count();
        $cancelamentosSemanaAnt = $salao->agendamentos()
            ->where('status', $cancelado)
            ->whereBetween('data_hora_inicio', [$inicioSemanaAnt, $fimSemanaAnt])
            ->count();

        if ($cancelamentosSemana >= 3 && $cancelamentosSemana > $cancelamentosSemanaAnt) {
            $alertas[] = [
                'tipo'      => 'warning',
                'titulo'    => 'Cancelamentos em alta',
                'mensagem'  => $cancelamentosSemana.' cancelamentos nesta semana'
                    .($cancelamentosSemanaAnt > 0
                        ? ' ('.$cancelamentosSemanaAnt.' na semana anterior).'
                        : '.'),
                'url'       => route('dono.agendamentos.index'),
                'url_label' => 'Ver agenda',
            ];
        }

        return $alertas;
    }

    private function deltaPercentual(float|int $atual, float|int $anterior): ?float
    {
        if ((float) $anterior == 0.0) {
            return (float) $atual > 0 ? 100.0 : null;
        }

        return round((((float) $atual - (float) $anterior) / (float) $anterior) * 100, 1);
    }

    public function donoManicures(Salao $salao)
    {
        return $salao->manicures()
            ->withCount(['agendamentos as agendamentos_hoje' => fn ($q) => $q->whereDate('data_hora_inicio', today()),
            ])
            ->withAvg('avaliacoes as nota_media_calc', 'nota')
            ->get();
    }

    public function donoProximos(Salao $salao, int $limit = 8)
    {
        return $salao->agendamentos()
            ->where('data_hora_inicio', '>=', now())
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
                AgendamentoStatus::Concluido->value,
            ])
            ->with($this->withClienteFaltas())
            ->orderBy('data_hora_inicio')
            ->take($limit)
            ->get();
    }

    public function donoDadosSemana(Salao $salao): Collection
    {
        return collect(range(6, 0))->reverse()->map(function ($i) use ($salao) {
            $dia = now()->subDays($i);

            return [
                'dia'   => $dia->format('d/m'),
                'total' => $salao->agendamentos()
                    ->whereDate('data_hora_inicio', $dia->toDateString())
                    ->count(),
                'faturamento' => (float) $salao->agendamentos()
                    ->concluidos()
                    ->whereDate('data_hora_inicio', $dia->toDateString())
                    ->sum('valor_total'),
            ];
        })->values();
    }

    public function donoServicosPopulares(Salao $salao, int $limit = 5)
    {
        return Servico::withCount(['agendamentos as total_mes' => fn ($q) => $q->where('agendamentos.salao_id', $salao->id)
            ->whereMonth('agendamentos.data_hora_inicio', now()->month),
        ])
            ->where('salao_id', $salao->id)
            ->orderByDesc('total_mes')
            ->take($limit)
            ->get();
    }

    /** Quantidade de produtos ativos com estoque no mínimo ou abaixo. */
    public function donoBaixoEstoque(Salao $salao): int
    {
        return Produto::where('salao_id', $salao->id)
            ->where('ativo', true)
            ->estoqueBaixo()
            ->count();
    }

    // ===== MANICURE =====

    public function manicureHoje(Manicure $manicure)
    {
        return $manicure->agendamentos()
            ->whereDate('data_hora_inicio', today())
            ->with(['servicos', 'cliente'])
            ->orderBy('data_hora_inicio')
            ->get();
    }

    public function manicureProximo(Manicure $manicure): ?Agendamento
    {
        return $manicure->agendamentos()
            ->where('data_hora_inicio', '>=', now())
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
                AgendamentoStatus::Concluido->value,
            ])
            ->orderBy('data_hora_inicio')
            ->first();
    }

    public function manicureMetricasMes(Manicure $manicure): array
    {
        $faturamentoMes = (float) $manicure->agendamentos()
            ->concluidos()
            ->doMes()
            ->sum('valor_total');

        return [
            'totalMes' => $manicure->agendamentos()
                ->concluidos()
                ->doMes()
                ->count(),
            'faturamentoMes'  => $faturamentoMes,
            'comissaoMes'     => $faturamentoMes * ($manicure->comissao / 100),
            'notaMedia'       => $manicure->nota_media,
            'totalAvaliacoes' => $manicure->avaliacoes()->count(),
        ];
    }

    public function manicureProximos7Dias(Manicure $manicure)
    {
        return $manicure->agendamentos()
            ->whereBetween('data_hora_inicio', [now(), now()->addDays(7)])
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
            ])
            ->with(['servicos', 'cliente'])
            ->orderBy('data_hora_inicio')
            ->get();
    }
}
