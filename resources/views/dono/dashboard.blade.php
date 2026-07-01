@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard — ' . ($salao?->nome ?? 'Meu Salão'))

@section('content')
@if(!$salao)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Sua conta não está vinculada a nenhum salão. Entre em contato com o administrador.
    </div>
@else

{{-- STATS --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-calendar-day" color="pink" :value="$totalHoje" label="Agendamentos Hoje" :subtitle="$concluidosHoje . ' concluídos'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-money-bill-wave" color="green" :value="'R$ ' . number_format($faturamentoHoje, 2, ',', '.')" label="Faturamento Hoje" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-calendar-check" color="blue" :value="$totalMes" label="Agendamentos do Mês" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-users" color="purple" :value="$totalClientes" label="Clientes Ativos" />
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Gráfico Semanal --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar text-pink me-2"></i> Últimos 7 dias
                </h5>
                <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink btn-sm">
                    <i class="fas fa-plus me-1"></i> Novo Agendamento
                </a>
            </div>
            <div class="card-body">
                <div id="chartSemana"></div>
            </div>
        </div>
    </div>

    {{-- Manicures --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-hand-sparkles text-pink me-2"></i> Equipe Hoje
                </h5>
            </div>
            <div class="card-body p-0">
                @forelse($manicures as $m)
                    <div class="list-item d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <img src="{{ $m->foto_url }}" alt="{{ $m->nome }}" width="40" height="40" class="rounded-circle me-3">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $m->nome }}</div>
                            <small class="text-muted">{{ $m->agendamentos_hoje }} agendamentos hoje</small>
                        </div>
                        <span class="badge {{ $m->ativo ? 'bg-success' : 'bg-secondary' }}">
                            {{ $m->ativo ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-hand-sparkles fa-2x mb-2"></i>
                        <p>Nenhuma manicure cadastrada</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Agenda de Hoje --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day text-pink me-2"></i> Agenda de Hoje
                </h5>
                <span class="badge bg-pink">{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($agendamentosHoje as $ag)
                    <div class="agenda-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-start">
                            <div class="agenda-time me-3 text-pink fw-bold">
                                {{ $ag->data_hora_inicio->format('H:i') }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->nome_cliente_exibido }}</div>
                                <small class="text-muted">
                                    <i class="fas fa-hand-sparkles me-1"></i>{{ $ag->manicure?->nome }} •
                                    {{ $ag->servicos->pluck('nome')->implode(', ') }}
                                </small>
                            </div>
                            <x-badge-status :status="$ag->status" class="ms-2" />
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                        <p>Nenhum agendamento hoje</p>
                        <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink btn-sm">Adicionar</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Próximos Agendamentos --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock text-pink me-2"></i> Próximos Agendamentos
                </h5>
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($proximosAgendamentos as $ag)
                    <div class="agenda-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-center">
                                <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                                <small class="text-muted">{{ $ag->data_hora_inicio->format('d/m') }}</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->nome_cliente_exibido }}</div>
                                <small class="text-muted">{{ $ag->servicos->pluck('nome')->implode(', ') }}</small>
                            </div>
                            <x-badge-status :status="$ag->status" />
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <p>Nenhum agendamento futuro</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
const dias = @json(collect($dadosSemana)->pluck('dia'));
const agendamentos = @json(collect($dadosSemana)->pluck('total'));
const faturamentos = @json(collect($dadosSemana)->pluck('faturamento'));

new ApexCharts(document.querySelector('#chartSemana'), {
    series: [
        { name: 'Agendamentos', type: 'column', data: agendamentos },
        { name: 'Faturamento (R$)', type: 'line', data: faturamentos }
    ],
    chart: { height: 280, type: 'line', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#e91e8c', '#9c27b0'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
    stroke: { width: [0, 3] },
    xaxis: { categories: dias },
    yaxis: [
        { labels: { style: { colors: '#666' } } },
        { opposite: true, labels: { formatter: v => 'R$' + v.toFixed(0), style: { colors: '#666' } } }
    ],
    legend: { position: 'top' },
    tooltip: { shared: true, intersect: false }
}).render();
</script>
@endpush
