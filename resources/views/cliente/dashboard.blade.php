@extends('layouts.app')

@section('title', 'Minha Área')
@section('page-title', 'Bem-vinda, ' . auth()->user()->name . '! 💅')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-4">
        <x-stat-card icon="fa-calendar-check" color="pink"   :value="$totalVisitas" label="Total de Visitas" />
    </div>
    <div class="col-sm-4">
        <x-stat-card icon="fa-money-bill"     color="green"  :value="'R$ ' . number_format($totalGasto, 2, ',', '.')" label="Total Investido" />
    </div>
    <div class="col-sm-4">
        <x-stat-card icon="fa-star"           color="purple" :value="$pontos" label="Pontos de Fidelidade" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock text-pink me-2"></i> Próximos Agendamentos
                </h5>
                <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink btn-sm">
                    <i class="fas fa-plus me-1"></i> Agendar
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($proximosAgendamentos as $ag)
                    <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('d/m') }}</div>
                                <small>{{ $ag->data_hora_inicio->format('H:i') }}</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->salao?->nome }}</div>
                                <small class="text-muted">
                                    {{ $ag->manicure?->nome }} • {{ $ag->servicos->pluck('nome')->implode(', ') }}
                                </small>
                            </div>
                            <div class="text-end">
                                <x-badge-status :status="$ag->status" class="mb-1" />
                                <div>
                                    <a href="{{ route('cliente.agendamentos.show', $ag) }}" class="btn btn-sm btn-outline-pink">Ver</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-calendar-plus fa-2x mb-3 d-block"></i>
                        <p>Nenhum agendamento futuro</p>
                        <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink">
                            Agendar Agora
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Histórico --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-pink me-2"></i> Histórico Recente
                </h5>
            </div>
            <div class="card-body p-0">
                @forelse($historico as $ag)
                    <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="text-muted small">{{ $ag->data_hora_inicio->format('d/m/Y') }}</div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $ag->salao?->nome }}</div>
                                <small class="text-muted">{{ $ag->servicos->pluck('nome')->implode(', ') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="text-pink fw-bold">R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</div>
                                @if(!$ag->avaliacao)
                                    <a href="{{ route('cliente.agendamentos.show', $ag) }}" class="btn btn-sm btn-outline-pink">Avaliar</a>
                                @else
                                    <span class="text-warning">{{ $ag->avaliacao->estrelas }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Nenhum atendimento realizado ainda</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body text-center p-4">
                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" width="80" height="80" class="rounded-circle mb-3">
                <h5>{{ auth()->user()->name }}</h5>
                <p class="text-muted">{{ auth()->user()->email }}</p>
                @if($cliente?->aniversario_hoje)
                    <div class="alert alert-pink">
                        🎂 Feliz Aniversário! Que seus dias sejam lindos como suas unhas! 🌸
                    </div>
                @endif

                <div class="mt-3">
                    <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink w-100 mb-2">
                        <i class="fas fa-calendar-plus me-2"></i> Fazer Agendamento
                    </a>
                    <a href="{{ route('cliente.agendamentos.index') }}" class="btn btn-outline-pink w-100">
                        <i class="fas fa-list me-2"></i> Ver Todos os Agendamentos
                    </a>
                </div>
            </div>
        </div>

        @if($pontos > 0)
            <div class="card mt-4">
                <div class="card-body text-center p-4 bg-pink-gradient rounded">
                    <i class="fas fa-star fa-2x text-white mb-2"></i>
                    <h4 class="text-white fw-bold">{{ $pontos }} pontos</h4>
                    <p class="text-white-50 mb-0">Programa de Fidelidade</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
