@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard — ' . ($salao?->nome ?? 'Meu Salão'))

@section('content')
@if(!$salao)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
        Sua conta não está vinculada a nenhum salão. Entre em contato com o administrador.
    </div>
@else

@include('dono.dashboard._onboarding')

{{-- Ações rápidas (mesmo conjunto do FAB + command palette) --}}
<div class="quick-actions mb-4" role="navigation" aria-label="Ações rápidas">
    @foreach($quickActions as $action)
        @if(!empty($action['action']))
            <button type="button"
                    class="quick-action"
                    data-action="{{ $action['action'] }}"
                    title="{{ $action['label'] }}{{ !empty($action['kbd']) ? ' ('.$action['kbd'].')' : '' }}">
                <i class="fas {{ $action['icon'] }}" aria-hidden="true"></i>
                <span>{{ $action['label'] }}</span>
                @if(!empty($action['kbd']))
                    <kbd class="quick-action-kbd">{{ $action['kbd'] }}</kbd>
                @endif
            </button>
        @else
            <a href="{{ $action['url'] }}"
               class="quick-action{{ !empty($action['primary']) ? ' is-primary' : '' }}">
                <i class="fas {{ $action['icon'] }}" aria-hidden="true"></i>
                <span>{{ $action['label'] }}</span>
            </a>
        @endif
    @endforeach
</div>

{{-- Alerta: clientes com risco de no-show na agenda de hoje --}}
@if($clientesRiscoHoje->isNotEmpty())
    <div class="alert alert-warning alert-permanent mb-4 d-flex align-items-start gap-2" role="status">
        <i class="fas fa-triangle-exclamation mt-1" aria-hidden="true"></i>
        <div>
            <strong>Risco de no-show hoje:</strong>
            {{ $clientesRiscoHoje->count() }}
            {{ \Illuminate\Support\Str::plural('cliente', $clientesRiscoHoje->count()) }}
            com histórico de faltas —
            {{ $clientesRiscoHoje->map(fn ($ag) => $ag->nome_cliente_exibido)->implode(', ') }}.
            Confirme presença com antecedência.
        </div>
    </div>
@endif

{{-- Alerta: produtos com estoque no mínimo ou zerado --}}
@if(($baixoEstoque ?? 0) > 0)
    <div class="alert alert-warning alert-permanent mb-4 d-flex align-items-start gap-2" role="status">
        <i class="fas fa-box mt-1" aria-hidden="true"></i>
        <div>
            <strong>Estoque baixo:</strong>
            {{ $baixoEstoque }}
            {{ \Illuminate\Support\Str::plural('produto', $baixoEstoque) }}
            com estoque no mínimo ou zerado.
            <a href="{{ route('dono.produtos.index') }}" class="alert-link">Ver produtos</a>
        </div>
    </div>
@endif

{{-- Alertas de negócio (CRM / cancelamentos) --}}
@foreach(($alertas ?? []) as $alerta)
    <div class="alert alert-{{ $alerta['tipo'] }} alert-permanent mb-4 d-flex align-items-start gap-2" role="status">
        <i class="fas fa-lightbulb mt-1" aria-hidden="true"></i>
        <div>
            <strong>{{ $alerta['titulo'] }}:</strong>
            {{ $alerta['mensagem'] }}
            @if(!empty($alerta['url']))
                <a href="{{ $alerta['url'] }}" class="alert-link">{{ $alerta['url_label'] ?? 'Ver' }}</a>
            @endif
        </div>
    </div>
@endforeach

{{-- STATS --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            icon="fa-calendar-day"
            color="pink"
            :value="$totalHoje"
            label="Agendamentos Hoje"
            :subtitle="$concluidosHoje . ' concluídos'"
            :href="route('dono.agendamentos.index', ['data' => today()->toDateString()])"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            icon="fa-money-bill-wave"
            color="green"
            :value="'R$ ' . number_format($faturamentoMes, 2, ',', '.')"
            label="Faturamento do Mês"
            :delta="$deltaFaturamentoPct ?? null"
            :href="auth()->user()->isDono() || auth()->user()->isAdmin() ? route('dono.financeiro.index') : null"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            icon="fa-calendar-check"
            color="blue"
            :value="$totalMes"
            label="Agendamentos do Mês"
            :delta="$deltaAgendamentosPct ?? null"
            :href="route('dono.agendamentos.index')"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            icon="fa-user-plus"
            color="purple"
            :value="$novosClientesMes ?? 0"
            label="Novos Clientes no Mês"
            :subtitle="$totalClientes . ' no total'"
            :delta="$deltaNovosClientesPct ?? null"
            :href="route('dono.clientes.index')"
        />
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Gráfico Semanal --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar text-pink me-2" aria-hidden="true"></i> Últimos 7 dias
                </h5>
                <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink btn-sm">
                    <i class="fas fa-calendar-plus me-1" aria-hidden="true"></i> Novo agendamento
                </a>
            </div>
            <div class="card-body">
                <div id="chartSemanaSkeleton" aria-hidden="true">
                    <div class="skeleton skeleton-line mb-3" style="height:18px;width:36%;display:block"></div>
                    <div class="skeleton" style="height:220px;width:100%;display:block;border-radius:12px"></div>
                </div>
                <div id="chartSemana" class="d-none" role="img" aria-label="Gráfico de agendamentos e faturamento dos últimos 7 dias"></div>
            </div>
        </div>
    </div>

    {{-- Manicures --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-hand-sparkles text-pink me-2" aria-hidden="true"></i> Equipe Hoje
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
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-hand-sparkles" aria-hidden="true"></i></div>
                        <h6 class="fw-bold">Nenhuma manicure cadastrada</h6>
                        <p class="mb-0">Cadastre a equipe no painel admin para ver a agenda por profissional.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Agenda de Hoje --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day text-pink me-2" aria-hidden="true"></i> Agenda de Hoje
                </h5>
                <span class="badge bg-pink">{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($agendamentosHoje as $ag)
                    @php($risco = $ag->cliente && $ag->cliente->eh_risco_no_show)
                    <a href="{{ route('dono.agendamentos.show', $ag) }}"
                       class="agenda-item d-block text-decoration-none text-reset p-3 {{ !$loop->last ? 'border-bottom' : '' }}{{ $risco ? ' is-risco-no-show' : '' }}">
                        <div class="d-flex align-items-start">
                            <div class="agenda-time me-3 text-pink fw-bold">
                                {{ $ag->data_hora_inicio->format('H:i') }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold d-flex align-items-center flex-wrap gap-2">
                                    {{ $ag->nome_cliente_exibido }}
                                    @if($risco)
                                        <span class="badge badge-risco-no-show"
                                              title="{{ $ag->cliente->total_faltas }} {{ \Illuminate\Support\Str::plural('falta', $ag->cliente->total_faltas) }}">
                                            <i class="fas fa-triangle-exclamation me-1" aria-hidden="true"></i>Risco no-show
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-hand-sparkles me-1" aria-hidden="true"></i>{{ $ag->manicure?->nome }} •
                                    {{ $ag->servicos->pluck('nome')->implode(', ') }}
                                </small>
                            </div>
                            <x-badge-status :status="$ag->status" class="ms-2" />
                        </div>
                    </a>
                @empty
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-calendar-times" aria-hidden="true"></i></div>
                        <h6 class="fw-bold">Nenhum agendamento hoje</h6>
                        <p>Que tal preencher a agenda?</p>
                        <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink btn-sm">
                            <i class="fas fa-calendar-plus me-1" aria-hidden="true"></i> Novo agendamento
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Próximos Agendamentos --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock text-pink me-2" aria-hidden="true"></i> Próximos Agendamentos
                </h5>
                <a href="{{ route('dono.agendamentos.index') }}" class="btn btn-ghost btn-sm">Ver todos</a>
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($proximosAgendamentos as $ag)
                    @php($risco = $ag->cliente && $ag->cliente->eh_risco_no_show)
                    <a href="{{ route('dono.agendamentos.show', $ag) }}"
                       class="agenda-item d-block text-decoration-none text-reset p-3 {{ !$loop->last ? 'border-bottom' : '' }}{{ $risco ? ' is-risco-no-show' : '' }}">
                        <div class="d-flex align-items-start">
                            <div class="me-3 text-center">
                                <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                                <small class="text-muted">{{ $ag->data_hora_inicio->format('d/m') }}</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold d-flex align-items-center flex-wrap gap-2">
                                    {{ $ag->nome_cliente_exibido }}
                                    @if($risco)
                                        <span class="badge badge-risco-no-show"
                                              title="{{ $ag->cliente->total_faltas }} {{ \Illuminate\Support\Str::plural('falta', $ag->cliente->total_faltas) }}">
                                            <i class="fas fa-triangle-exclamation me-1" aria-hidden="true"></i>Risco no-show
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $ag->servicos->pluck('nome')->implode(', ') }}</small>
                            </div>
                            <x-badge-status :status="$ag->status" />
                        </div>
                    </a>
                @empty
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></div>
                        <h6 class="fw-bold">Nenhum agendamento futuro</h6>
                        <p>Crie um horário ou cadastre um cliente novo.</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink btn-sm">
                                <i class="fas fa-calendar-plus me-1" aria-hidden="true"></i> Novo agendamento
                            </a>
                            <a href="{{ route('dono.clientes.create') }}" class="btn btn-outline-pink btn-sm">
                                <i class="fas fa-user-plus me-1" aria-hidden="true"></i> Novo cliente
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($servicosPopulares->isNotEmpty())
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-fire text-pink me-2" aria-hidden="true"></i> Serviços populares no mês
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($servicosPopulares as $servico)
                        <span class="badge bg-pink-light text-pink px-3 py-2">
                            {{ $servico->nome }}
                            <span class="ms-1 opacity-75">{{ $servico->total_mes }}×</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endif
@endsection

@if($salao ?? false)
@push('scripts')
<script>
(function () {
    const dias = @json(collect($dadosSemana)->pluck('dia'));
    const agendamentos = @json(collect($dadosSemana)->pluck('total'));
    const faturamentos = @json(collect($dadosSemana)->pluck('faturamento'));
    const el = document.querySelector('#chartSemana');
    const skeleton = document.querySelector('#chartSemanaSkeleton');

    const chart = new ApexCharts(el, {
        series: [
            { name: 'Agendamentos', type: 'column', data: agendamentos },
            { name: 'Faturamento (R$)', type: 'line', data: faturamentos }
        ],
        chart: {
            height: 280,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'inherit',
            animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches }
        },
        colors: ['#e91e8c', '#22c55e'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        stroke: { width: [0, 3] },
        xaxis: { categories: dias },
        yaxis: [
            { labels: { style: { colors: '#666' } } },
            { opposite: true, labels: { formatter: v => 'R$' + v.toFixed(0), style: { colors: '#666' } } }
        ],
        legend: { position: 'top' },
        tooltip: { shared: true, intersect: false },
        noData: { text: 'Sem dados nesta semana' }
    });

    chart.render().then(function () {
        skeleton?.classList.add('d-none');
        el.classList.remove('d-none');
    });
})();
</script>
@endpush
@endif
