@extends('layouts.app')

@section('title', $cliente->nome)
@section('page-title', $cliente->nome)

@section('content')
<div class="row g-4">
    {{-- Sidebar info --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold mb-3"
                     style="width:90px;height:90px;background:var(--gradient-pink);font-size:32px">
                    {{ strtoupper(substr($cliente->nome, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $cliente->nome }}</h5>
                <span class="badge {{ $cliente->ativo ? 'bg-success' : 'bg-secondary' }} mb-3">
                    {{ $cliente->ativo ? 'Ativo' : 'Inativo' }}
                </span>
                <a href="{{ route('dono.clientes.edit', $cliente) }}" class="btn btn-pink btn-sm w-100">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0 fw-bold">Informações</h6></div>
            <div class="card-body">
                @if($cliente->telefone)
                    <div class="mb-2"><i class="fas fa-phone text-pink me-2"></i>{{ $cliente->telefone }}</div>
                @endif
                @if($cliente->email)
                    <div class="mb-2"><i class="fas fa-envelope text-pink me-2"></i>{{ $cliente->email }}</div>
                @endif
                @if($cliente->cpf)
                    <div class="mb-2"><i class="fas fa-id-card text-pink me-2"></i>{{ $cliente->cpf }}</div>
                @endif
                @if($cliente->data_nascimento)
                    <div class="mb-2">
                        <i class="fas fa-cake-candles text-pink me-2"></i>
                        {{ $cliente->data_nascimento->format('d/m/Y') }}
                        @if($cliente->idade)
                            ({{ $cliente->idade }} anos)
                        @endif
                    </div>
                @endif
                @if($cliente->endereco)
                    <div class="mb-2"><i class="fas fa-location-dot text-pink me-2"></i>{{ $cliente->endereco }}</div>
                @endif
                @if($cliente->alergias)
                    <hr>
                    <div class="alert alert-warning small mb-0">
                        <strong><i class="fas fa-exclamation-triangle me-1"></i>Alergias:</strong> {{ $cliente->alergias }}
                    </div>
                @endif
                @if($cliente->observacoes)
                    <hr>
                    <small class="text-muted">{{ $cliente->observacoes }}</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Estatísticas + histórico --}}
    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-blue-light"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $cliente->total_visitas }}</div>
                        <div class="stat-label">Visitas</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-green-light"><i class="fas fa-money-bill"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">R$ {{ number_format($cliente->total_gasto, 0) }}</div>
                        <div class="stat-label">Total gasto</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-yellow-light"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $cliente->pontos_fidelidade }}</div>
                        <div class="stat-label">Pontos fidelidade</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fas fa-history text-pink me-2"></i>Histórico de agendamentos</h6></div>
            <div class="card-body p-0">
                @forelse($cliente->agendamentos as $ag)
                    <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 text-center" style="min-width:60px">
                            <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('d/m') }}</div>
                            <small class="text-muted">{{ $ag->data_hora_inicio->format('H:i') }}</small>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $ag->servicos->pluck('nome')->implode(' + ') }}</div>
                            <small class="text-muted">{{ $ag->manicure?->nome }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-pink">R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</div>
                            <span class="badge bg-{{ $ag->status_color }}">{{ $ag->status_label }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-calendar"></i></div>
                        <p class="mb-0">Nenhum agendamento registrado</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
