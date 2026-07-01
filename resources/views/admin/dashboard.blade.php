@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Administrativo')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-pink-light">
                <i class="fas fa-store text-pink"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalSaloes }}</div>
                <div class="stat-label">Salões</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-purple-light">
                <i class="fas fa-hand-sparkles text-purple"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalManicures }}</div>
                <div class="stat-label">Manicures</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-blue-light">
                <i class="fas fa-users text-blue"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalClientes }}</div>
                <div class="stat-label">Clientes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-green-light">
                <i class="fas fa-money-bill-wave text-green"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">R$ {{ number_format($faturamentoMes, 2, ',', '.') }}</div>
                <div class="stat-label">Faturamento do Mês</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Gráfico de agendamentos -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar text-pink me-2"></i> Agendamentos & Faturamento (6 meses)
                </h5>
            </div>
            <div class="card-body">
                <div id="chartAgendamentos"></div>
            </div>
        </div>
    </div>
    <!-- Salões mais ativos -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy text-pink me-2"></i> Top Salões do Mês
                </h5>
            </div>
            <div class="card-body p-0">
                @forelse($saloesMaisAtivos as $i => $salao)
                    <div class="list-item d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="rank-badge me-3">{{ $i + 1 }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $salao->nome }}</div>
                            <small class="text-muted">{{ $salao->agendamentos_count }} agendamentos</small>
                        </div>
                        <a href="{{ route('admin.saloes.show', $salao) }}" class="btn btn-sm btn-outline-pink">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Nenhum dado disponível</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Últimos agendamentos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-check text-pink me-2"></i> Últimos Agendamentos
        </h5>
        <span class="badge bg-pink">{{ $agendamentosHoje }} hoje</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Manicure</th>
                        <th>Salão</th>
                        <th>Data/Hora</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agendamentosRecentes as $ag)
                        <tr>
                            <td>{{ $ag->id }}</td>
                            <td>{{ $ag->nome_cliente_exibido }}</td>
                            <td>{{ $ag->manicure?->nome }}</td>
                            <td>{{ $ag->salao?->nome }}</td>
                            <td>{{ $ag->data_hora_inicio->format('d/m/Y H:i') }}</td>
                            <td>R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $ag->status_color }}">{{ $ag->status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum agendamento ainda</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const meses = @json($dadosMeses->pluck('mes'));
const totais = @json($dadosMeses->pluck('total'));
const faturamentos = @json($dadosMeses->pluck('faturamento'));

const options = {
    series: [
        { name: 'Agendamentos', type: 'column', data: totais },
        { name: 'Faturamento (R$)', type: 'line', data: faturamentos }
    ],
    chart: { height: 300, type: 'line', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#e91e8c', '#9c27b0'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    stroke: { width: [0, 3] },
    xaxis: { categories: meses },
    yaxis: [
        { title: { text: 'Agendamentos' }, labels: { style: { colors: '#666' } } },
        { opposite: true, title: { text: 'Faturamento (R$)' }, labels: {
            formatter: v => 'R$ ' + v.toFixed(0), style: { colors: '#666' }
        }}
    ],
    legend: { position: 'top' },
    tooltip: { shared: true, intersect: false }
};

new ApexCharts(document.querySelector('#chartAgendamentos'), options).render();
</script>
@endpush
