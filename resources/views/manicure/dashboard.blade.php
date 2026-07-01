@extends('layouts.app')

@section('title', 'Meu Dashboard')
@section('page-title', 'Bem-vinda, ' . ($manicure?->nome ?? auth()->user()->name) . '!')

@section('content')
@if(!$manicure)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Seu perfil de manicure ainda não foi configurado. Entre em contato com o salão.
    </div>
@else

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-calendar-day" color="pink"  :value="$agendamentosHoje->count()" label="Atendimentos Hoje" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-check-circle" color="green" :value="$totalMes" label="Concluídos no Mês" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-money-bill"   color="blue"  :value="'R$ ' . number_format($comissaoMes, 2, ',', '.')" label="Minha Comissão" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card icon="fa-star" color="yellow" :value="$notaMedia . ' ★'" label="Nota Média ({{ $totalAvaliacoes }} avaliações)" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day text-pink me-2"></i> Hoje
                </h5>
                @if($proximoAgendamento)
                    <span class="badge bg-pink">Próximo: {{ $proximoAgendamento->data_hora_inicio->format('H:i') }}</span>
                @endif
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($agendamentosHoje as $ag)
                    <div class="agenda-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-start">
                            <div class="agenda-time me-3 text-pink fw-bold text-center">
                                <div>{{ $ag->data_hora_inicio->format('H:i') }}</div>
                                <small class="text-muted">{{ $ag->duracao_minutos }}min</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->nome_cliente_exibido }}</div>
                                <small class="text-muted">{{ $ag->servicos->pluck('nome')->implode(' + ') }}</small>
                                <div class="mt-1">
                                    <x-badge-status :status="$ag->status" />
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-pink">R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</div>
                                <a href="{{ route('manicure.agenda.show', $ag) }}" class="btn btn-sm btn-ghost">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-umbrella-beach fa-2x mb-3 d-block"></i>
                        Nenhum agendamento hoje! Aproveite 🌸
                    </div>
                @endforelse
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('manicure.agenda.index') }}" class="btn btn-outline-pink">
                    <i class="fas fa-calendar-alt me-1"></i> Ver Agenda Completa
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock text-pink me-2"></i> Próximos 7 dias
                </h5>
            </div>
            <div class="card-body p-0 scroll-y-400">
                @forelse($proximosAgendamentos as $ag)
                    <div class="agenda-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-center min-w-50">
                                <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                                <small class="text-muted">{{ $ag->data_hora_inicio->format('d/m') }}</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->nome_cliente_exibido }}</div>
                                <small class="text-muted">{{ $ag->servicos->pluck('nome')->implode(' + ') }}</small>
                            </div>
                            <x-badge-status :status="$ag->status" />
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Nenhum agendamento nos próximos dias</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif
@endsection
