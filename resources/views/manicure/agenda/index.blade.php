@extends('layouts.app')

@section('title', 'Minha Agenda')
@section('page-title', 'Minha Agenda')

@section('content')
@php
    $hoje = today()->toDateString();
    $dataStr = $data->toDateString();
    $limiteNoShow = (int) config('manicure.no_show.limite_alerta', 2);
@endphp

{{-- Navegação Semanal --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
            <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->subWeek()->toDateString()]) }}"
               class="btn btn-outline-pink"
               aria-label="Semana anterior"
               title="Semana anterior">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span class="d-none d-sm-inline ms-1">Semana</span>
            </a>

            <div class="text-center flex-grow-1">
                <h5 class="mb-1 text-pink">
                    Semana de {{ $data->copy()->startOfWeek(0)->format('d/m') }}
                    a {{ $data->copy()->endOfWeek(6)->format('d/m/Y') }}
                </h5>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->subDay()->toDateString()]) }}"
                       class="btn btn-sm btn-outline-secondary"
                       aria-label="Dia anterior">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span class="d-none d-md-inline">Dia</span>
                    </a>
                    @if($dataStr !== $hoje)
                        <a href="{{ route('manicure.agenda.index', ['data' => $hoje]) }}"
                           class="btn btn-sm btn-pink">
                            Hoje
                        </a>
                    @else
                        <span class="btn btn-sm btn-pink disabled" aria-current="date">Hoje</span>
                    @endif
                    <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->addDay()->toDateString()]) }}"
                       class="btn btn-sm btn-outline-secondary"
                       aria-label="Próximo dia">
                        <span class="d-none d-md-inline">Dia</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <a href="{{ route('manicure.agenda.index', ['data' => $data->copy()->addWeek()->toDateString()]) }}"
               class="btn btn-outline-pink"
               aria-label="Próxima semana"
               title="Próxima semana">
                <span class="d-none d-sm-inline me-1">Semana</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>

        <div class="agenda-week-scroll d-flex gap-2 overflow-auto pb-1" role="tablist" aria-label="Dias da semana">
            @foreach($semana as $dia)
                @php
                    $selecionado = $dia['data']->toDateString() === $dataStr;
                    $ehHoje = $dia['data']->toDateString() === $hoje;
                    $folgaDia = $dia['folga'] ?? null;
                @endphp
                <a href="{{ route('manicure.agenda.index', ['data' => $dia['data']->toDateString()]) }}"
                   class="agenda-day-btn btn flex-shrink-0 {{ $selecionado ? 'btn-pink' : 'btn-outline-secondary' }} {{ $ehHoje && !$selecionado ? 'agenda-day-hoje' : '' }}"
                   role="tab"
                   aria-selected="{{ $selecionado ? 'true' : 'false' }}"
                   @if($folgaDia) title="Folga: {{ $folgaDia->dia_todo ? 'dia inteiro' : ($folgaDia->hora_inicio.' – '.$folgaDia->hora_fim) }}" @endif>
                    <div class="small text-uppercase">{{ $dia['data']->translatedFormat('D') }}</div>
                    <div class="fw-bold fs-5 lh-1">{{ $dia['data']->format('d') }}</div>
                    <div class="agenda-day-meta mt-1">
                        @if($dia['total'] > 0)
                            <span class="badge {{ $selecionado ? 'bg-white text-pink' : 'bg-pink' }}">{{ $dia['total'] }}</span>
                        @else
                            <span class="badge {{ $selecionado ? 'bg-white text-muted' : 'bg-light text-muted' }}">0</span>
                        @endif
                        @if($folgaDia)
                            <span class="badge {{ $selecionado ? 'bg-white text-warning' : 'bg-warning text-dark' }}" aria-label="Folga">
                                <i class="fas fa-umbrella-beach" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                    @if($ehHoje)
                        <div class="agenda-day-hoje-dot {{ $selecionado ? 'is-selected' : '' }}" aria-hidden="true"></div>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Agendamentos do Dia --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-day text-pink me-2" aria-hidden="true"></i>
            {{ $data->translatedFormat('l, d \d\e F') }}
        </h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('manicure.agenda.ical', ['data' => $data->toDateString()]) }}"
               class="btn btn-sm btn-outline-pink"
               title="Exportar dia em .ics">
                <i class="fas fa-calendar-plus me-1" aria-hidden="true"></i> .ics
            </a>
            <span class="badge bg-pink">{{ $agendamentos->count() }} {{ \Illuminate\Support\Str::plural('atendimento', $agendamentos->count()) }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        @php
            $diaAtual = collect($semana)->first(fn ($d) => $d['data']->toDateString() === $dataStr);
            $folgaHoje = $diaAtual['folga'] ?? null;
        @endphp
        @if($folgaHoje)
            <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-start gap-2">
                <i class="fas fa-umbrella-beach mt-1" aria-hidden="true"></i>
                <div>
                    <strong>Você está de folga neste dia</strong>
                    <div class="small mb-0">
                        @if($folgaHoje->dia_todo)
                            Dia inteiro bloqueado na agenda
                        @else
                            Bloqueio parcial: {{ $folgaHoje->hora_inicio }} – {{ $folgaHoje->hora_fim }}
                        @endif
                        @if($folgaHoje->motivo)
                            · {{ $folgaHoje->motivo }}
                        @endif
                        · <a href="{{ route('manicure.folgas.index') }}" class="alert-link">Gerenciar folgas</a>
                    </div>
                </div>
            </div>
        @endif

        @forelse($agendamentos as $ag)
            @php
                $cliente = $ag->cliente;
                $temAlergia = filled($cliente?->alergias);
                $ehRisco = $cliente && $cliente->total_faltas >= $limiteNoShow;
            @endphp
            <div class="agenda-item p-3 p-md-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                        <div class="text-center flex-shrink-0" style="min-width:56px">
                            <div class="fw-bold text-pink fs-5 lh-1">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                            <small class="text-muted">{{ $ag->data_hora_fim->format('H:i') }}</small>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="fw-semibold fs-6 text-truncate">{{ $ag->nome_cliente_exibido }}</div>
                                <x-badge-status :status="$ag->status" />
                            </div>
                            <div class="text-muted small text-truncate">{{ $ag->servicos->pluck('nome')->implode(' + ') }}</div>
                            @if($ag->observacoes)
                                <small class="text-muted fst-italic d-block text-truncate">{{ $ag->observacoes }}</small>
                            @endif
                            @if($temAlergia || $ehRisco)
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    @if($temAlergia)
                                        <span class="badge bg-red-light text-danger"
                                              title="{{ $cliente->alergias }}">
                                            <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                                            Alergia
                                        </span>
                                    @endif
                                    @if($ehRisco)
                                        <span class="badge bg-yellow-light text-dark"
                                              title="{{ $cliente->total_faltas }} {{ \Illuminate\Support\Str::plural('falta', $cliente->total_faltas) }}">
                                            <i class="fas fa-user-clock me-1" aria-hidden="true"></i>
                                            Risco de falta ({{ $cliente->total_faltas }})
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-row flex-md-column align-items-center align-items-md-end justify-content-between gap-2 flex-shrink-0">
                        <div class="text-pink fw-bold">R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</div>
                        <div class="d-flex gap-2 agenda-actions">
                            @if(in_array($ag->status, ['aguardando', 'confirmado']))
                                <form method="POST" action="{{ route('manicure.agenda.status', $ag) }}"
                                      class="flex-grow-1"
                                      data-confirm="Iniciar atendimento?"
                                      data-confirm-message="Confirmar o início do atendimento de {{ $ag->nome_cliente_exibido }}?"
                                      data-confirm-type="info"
                                      data-confirm-ok="Iniciar">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="em_andamento">
                                    <button type="submit" class="btn btn-primary btn-sm w-100 agenda-action-btn">
                                        <i class="fas fa-play me-1" aria-hidden="true"></i>Iniciar
                                    </button>
                                </form>
                            @elseif($ag->status === 'em_andamento')
                                <button type="button"
                                        class="btn btn-pink btn-sm flex-grow-1 agenda-action-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalFinalizar{{ $ag->id }}">
                                    <i class="fas fa-check me-1" aria-hidden="true"></i>Finalizar
                                </button>
                            @endif
                            <a href="{{ route('manicure.agenda.show', $ag) }}"
                               class="btn btn-outline-secondary btn-sm agenda-action-btn"
                               aria-label="Ver detalhes">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="d-md-none ms-1">Detalhes</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if($ag->status === 'em_andamento')
                <div class="modal fade" id="modalFinalizar{{ $ag->id }}" tabindex="-1" aria-labelledby="modalFinalizarLabel{{ $ag->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalFinalizarLabel{{ $ag->id }}">Finalizar Atendimento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <form method="POST" action="{{ route('manicure.agenda.status', $ag) }}" class="agenda-status-form">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="concluido">
                                <div class="modal-body">
                                    <p class="mb-1"><strong>Cliente:</strong> {{ $ag->nome_cliente_exibido }}</p>
                                    <p class="mb-1"><strong>Serviços:</strong> {{ $ag->servicos->pluck('nome')->implode(', ') }}</p>
                                    <p class="mb-3"><strong>Valor:</strong> R$ {{ number_format($ag->valor_total, 2, ',', '.') }}</p>
                                    @if($temAlergia)
                                        <div class="alert alert-danger py-2 small">
                                            <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                                            <strong>Alergias:</strong> {{ $cliente->alergias }}
                                        </div>
                                    @endif
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="formaPagamento{{ $ag->id }}">Forma de Pagamento</label>
                                        <select name="forma_pagamento" id="formaPagamento{{ $ag->id }}" class="form-select" required>
                                            @foreach(\App\Models\Pagamento::FORMAS_LABELS as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold" for="gorjeta{{ $ag->id }}">Gorjeta (opcional)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" name="gorjeta" id="gorjeta{{ $ag->id }}" class="form-control"
                                                   min="0" step="0.01" value="0" placeholder="0,00">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-pink">
                                        <i class="fas fa-check me-2" aria-hidden="true"></i> Finalizar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="p-5 text-center text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block text-pink opacity-50" aria-hidden="true"></i>
                <p class="fs-5 mb-1">Nenhum agendamento para este dia</p>
                <p class="small mb-0">Use a navegação acima para ver outros dias.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
.agenda-week-scroll {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}
.agenda-day-btn {
    min-width: 64px;
    min-height: 76px;
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}
.agenda-day-meta {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
}
.agenda-day-hoje:not(.btn-pink) {
    border-color: var(--pink);
    box-shadow: inset 0 0 0 1px var(--pink);
}
.agenda-day-hoje-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--pink);
    margin-top: 4px;
}
.agenda-day-hoje-dot.is-selected {
    background: #fff;
}
.agenda-actions {
    width: 100%;
}
.agenda-action-btn {
    min-height: 40px;
}
@media (min-width: 768px) {
    .agenda-actions {
        width: auto;
    }
}
@media (max-width: 767.98px) {
    .agenda-action-btn {
        min-height: 44px;
    }
    .agenda-day-btn {
        min-width: 58px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .agenda-item,
    .agenda-item::before {
        transition: none;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.agenda-status-form').forEach(function (form) {
    form.addEventListener('submit', function () {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn || btn.dataset.submitting === '1') return;
        btn.dataset.submitting = '1';
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.dataset.originalHtml = original;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Salvando…';
    });
});
</script>
@endpush
