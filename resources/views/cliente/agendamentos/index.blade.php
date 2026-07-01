@extends('layouts.app')

@section('title', 'Meus Agendamentos')
@section('page-title', 'Meus Agendamentos')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-check text-pink me-2"></i> Histórico de Agendamentos
        </h5>
        <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink">
            <i class="fas fa-plus me-1"></i> Novo Agendamento
        </a>
    </div>
    <div class="card-body p-0">
        @forelse($agendamentos as $ag)
            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-center" style="min-width:56px">
                            <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('d/m') }}</div>
                            <small class="text-muted">{{ $ag->data_hora_inicio->format('H:i') }}</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fw-semibold">{{ $ag->salao?->nome }}</div>
                        <small class="text-muted">
                            <i class="fas fa-hand-sparkles me-1"></i>{{ $ag->manicure?->nome }} •
                            {{ $ag->servicos->pluck('nome')->implode(', ') }}
                        </small>
                        @if($ag->avaliacao && $ag->status === 'concluido')
                            <div class="text-warning small mt-1">{{ $ag->avaliacao->estrelas }}</div>
                        @endif
                    </div>
                    <div class="col-auto text-end">
                        <div class="fw-bold text-pink">R$ {{ number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.') }}</div>
                        <span class="badge bg-{{ $ag->status_color }}">{{ $ag->status_label }}</span>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('cliente.agendamentos.show', $ag) }}" class="btn btn-sm btn-outline-pink">
                            <i class="fas fa-eye me-1"></i> Ver
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-5 text-center text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block text-pink opacity-50"></i>
                <p class="fs-5">Você ainda não tem agendamentos</p>
                <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink">
                    <i class="fas fa-plus me-2"></i> Fazer Primeiro Agendamento
                </a>
            </div>
        @endforelse
    </div>
    @if(method_exists($agendamentos, 'hasPages') && $agendamentos->hasPages())
        <div class="card-footer">
            {{ $agendamentos->links() }}
        </div>
    @endif
</div>
@endsection
