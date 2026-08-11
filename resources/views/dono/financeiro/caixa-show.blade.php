@extends('layouts.app')

@section('title', 'Detalhe do caixa')
@section('page-title', 'Detalhe do caixa')

@section('content')
@include('dono.financeiro._nav')

<div class="mb-3">
    <a href="{{ route('dono.financeiro.caixa.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Status</div>
                @if($caixa->estaAberto())
                    <span class="badge text-bg-success">Aberto</span>
                @else
                    <span class="badge text-bg-secondary">Fechado</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Saldo inicial</div>
                <div class="fs-4 fw-bold">@money($caixa->saldo_inicial)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Saldo calculado</div>
                <div class="fs-4 fw-bold">@money($saldoCalculado)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Diferença</div>
                @if($caixa->diferenca !== null)
                    <div class="fs-4 fw-bold {{ (float)$caixa->diferenca === 0.0 ? '' : ((float)$caixa->diferenca > 0 ? 'text-success' : 'text-danger') }}">
                        @money($caixa->diferenca)
                    </div>
                    <small class="text-muted">Informado: @money($caixa->saldo_final_informado)</small>
                @else
                    <div class="fs-4 fw-bold text-muted">—</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-4">
                <div class="text-muted">Aberto em</div>
                <div class="fw-semibold">{{ $caixa->aberto_em->format('d/m/Y H:i') }}</div>
                <div class="text-muted">{{ $caixa->abertoPor?->name }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted">Fechado em</div>
                @if($caixa->fechado_em)
                    <div class="fw-semibold">{{ $caixa->fechado_em->format('d/m/Y H:i') }}</div>
                    <div class="text-muted">{{ $caixa->fechadoPor?->name }}</div>
                @else
                    <div class="fw-semibold">—</div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="text-muted">Observação</div>
                <div>{{ $caixa->observacao ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Movimentações</h5>
    </div>
    <div class="card-body p-0">
        @forelse($caixa->movimentacoes->sortBy('created_at') as $mov)
            <div class="d-flex align-items-center justify-content-between p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div>
                    <span class="badge text-bg-{{ $mov->isCredito() ? 'success' : 'danger' }} me-1">
                        {{ $mov->tipo_label }}
                    </span>
                    <span class="fw-semibold">{{ $mov->descricao }}</span>
                    <div class="small text-muted">
                        {{ $mov->created_at->format('d/m/Y H:i') }}
                        @if($mov->user) · {{ $mov->user->name }}@endif
                    </div>
                </div>
                <div class="fw-bold {{ $mov->isCredito() ? 'text-success' : 'text-danger' }}">
                    {{ $mov->isCredito() ? '+' : '−' }}@money($mov->valor)
                </div>
            </div>
        @empty
            <div class="empty-state py-4">
                <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
                <p class="mb-0">Nenhuma movimentação.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
