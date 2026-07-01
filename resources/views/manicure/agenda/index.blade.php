@extends('layouts.app')

@section('title', 'Minha Agenda')
@section('page-title', 'Minha Agenda')

@section('content')
{{-- Navegação Semanal --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->subWeek()->toDateString()]) }}"
               class="btn btn-outline-pink">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h5 class="mb-0 text-pink">Semana de {{ $data->copy()->startOfWeek(0)->format('d/m') }} a {{ $data->copy()->endOfWeek(6)->format('d/m/Y') }}</h5>
            <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->addWeek()->toDateString()]) }}"
               class="btn btn-outline-pink">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="row g-2">
            @foreach($semana as $dia)
                <div class="col">
                    <a href="{{ route('manicure.agenda.index', ['data' => $dia['data']->toDateString()]) }}"
                       class="btn w-100 {{ $dia['data']->toDateString() === $data->toDateString() ? 'btn-pink' : 'btn-outline-secondary' }}">
                        <div class="small">{{ $dia['data']->format('D') }}</div>
                        <div class="fw-bold">{{ $dia['data']->format('d') }}</div>
                        @if($dia['total'] > 0)
                            <span class="badge {{ $dia['data']->toDateString() === $data->toDateString() ? 'bg-white text-pink' : 'bg-pink' }}">{{ $dia['total'] }}</span>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Agendamentos do Dia --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-day text-pink me-2"></i>
            {{ $data->format('l, d \de F') }}
        </h5>
        <span class="badge bg-pink">{{ $agendamentos->count() }} atendimentos</span>
    </div>
    <div class="card-body p-0">
        @forelse($agendamentos as $ag)
            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-center" style="min-width:60px">
                            <div class="fw-bold text-pink fs-5">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                            <small class="text-muted">{{ $ag->data_hora_fim->format('H:i') }}</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fw-semibold fs-6">{{ $ag->nome_cliente_exibido }}</div>
                        <div class="text-muted">{{ $ag->servicos->pluck('nome')->implode(' + ') }}</div>
                        @if($ag->observacoes)
                            <small class="text-muted fst-italic">{{ $ag->observacoes }}</small>
                        @endif
                    </div>
                    <div class="col-auto">
                        <div class="text-pink fw-bold">R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</div>
                        <div class="mt-1">
                            <span class="badge bg-{{ $ag->status_color }}">{{ $ag->status_label }}</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        @if(in_array($ag->status, ['aguardando', 'confirmado']))
                            <form method="POST" action="{{ route('manicure.agenda.status', $ag) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="em_andamento">
                                <button class="btn btn-primary btn-sm">Iniciar</button>
                            </form>
                        @elseif($ag->status === 'em_andamento')
                            <button class="btn btn-pink btn-sm" data-bs-toggle="modal" data-bs-target="#modalFinalizar{{ $ag->id }}">Finalizar</button>
                        @endif
                        <a href="{{ route('manicure.agenda.show', $ag) }}" class="btn btn-outline-secondary btn-sm ms-1">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Modal Finalizar --}}
            @if($ag->status === 'em_andamento')
                <div class="modal fade" id="modalFinalizar{{ $ag->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Finalizar Atendimento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('manicure.agenda.status', $ag) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="concluido">
                                <div class="modal-body">
                                    <p><strong>Cliente:</strong> {{ $ag->nome_cliente_exibido }}</p>
                                    <p><strong>Serviços:</strong> {{ $ag->servicos->pluck('nome')->implode(', ') }}</p>
                                    <p><strong>Valor:</strong> R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</p>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Forma de Pagamento</label>
                                        <select name="forma_pagamento" class="form-select" required>
                                            @foreach(\App\Models\Pagamento::FORMAS_LABELS as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-pink">
                                        <i class="fas fa-check me-2"></i> Finalizar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="p-5 text-center text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block text-pink opacity-50"></i>
                <p class="fs-5">Nenhum agendamento para este dia</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
