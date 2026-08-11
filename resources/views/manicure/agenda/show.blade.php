@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
@php
    $cliente = $agendamento->cliente;
    $temAlergia = filled($cliente?->alergias);
    $ehRisco = $cliente?->eh_risco_no_show;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold mb-0">
        <i class="fa-solid fa-calendar-check me-2 text-pink"></i>Agendamento #{{ $agendamento->id }}
    </h2>
    <a href="{{ route('manicure.agenda.index', ['data' => $agendamento->data_hora_inicio->toDateString()]) }}"
       class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Minha Agenda
    </a>
</div>

@if($temAlergia)
    <div class="alert alert-danger alert-permanent d-flex align-items-start gap-2">
        <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
        <div>
            <strong>Alergias do cliente:</strong> {{ $cliente->alergias }}
        </div>
    </div>
@endif

@if($ehRisco)
    <div class="alert alert-warning alert-permanent d-flex align-items-start gap-2">
        <i class="fas fa-triangle-exclamation mt-1" aria-hidden="true"></i>
        <div>
            <strong>Atenção:</strong> este cliente já registra
            {{ $cliente->total_faltas }} {{ \Illuminate\Support\Str::plural('falta', $cliente->total_faltas) }}
            (não comparecimento). Considere confirmar presença com antecedência.
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Status Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <x-badge-status :status="$agendamento->status" class="fs-6 px-3 py-2" />
                    <span class="text-muted small">
                        <i class="fa-solid fa-clock me-1"></i>
                        {{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}
                        —
                        {{ $agendamento->data_hora_fim->format('H:i') }}
                        ({{ $agendamento->duracao_minutos }} min)
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold text-uppercase">Cliente</label>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <i class="fa-solid fa-user text-pink"></i>
                            <span class="fw-semibold">{{ $agendamento->nome_cliente_exibido }}</span>
                        </div>
                        @if($agendamento->cliente?->telefone ?? $agendamento->telefone_cliente)
                        <div class="text-muted small mt-1">
                            <i class="fa-solid fa-phone me-1"></i>
                            {{ $agendamento->cliente?->telefone ?? $agendamento->telefone_cliente }}
                        </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold text-uppercase">Origem</label>
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border">
                                @switch($agendamento->origem)
                                    @case('app') <i class="fa-solid fa-mobile me-1"></i>App @break
                                    @case('web')
                                    @case('site') <i class="fa-solid fa-globe me-1"></i>Site @break
                                    @case('guest') <i class="fa-solid fa-user me-1"></i>Convidado @break
                                    @case('telefone') <i class="fa-solid fa-phone me-1"></i>Telefone @break
                                    @default <i class="fa-solid fa-store me-1"></i>Presencial
                                @endswitch
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Serviços --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-hand-sparkles me-2 text-pink"></i>Serviços</h6>
            </div>
            <div class="list-group list-group-flush">
                @foreach($agendamento->servicos as $servico)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold">{{ $servico->nome }}</span>
                        <span class="text-muted small ms-2">
                            <i class="fa-regular fa-clock me-1"></i>{{ $servico->duracao }} min
                        </span>
                        @if($servico->combo)
                            <span class="badge bg-warning text-dark ms-1">Combo</span>
                        @endif
                    </div>
                    <span class="fw-bold text-pink">
                        R$ {{ number_format($servico->pivot->preco ?? $servico->preco, 2, ',', '.') }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="card-footer bg-light d-flex justify-content-between fw-bold">
                <span>Total dos serviços</span>
                <span class="text-pink fs-5">R$ {{ number_format($agendamento->valor_total, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Observações --}}
        @if($agendamento->observacoes)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-note-sticky me-2 text-pink"></i>Observações</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $agendamento->observacoes }}</p>
            </div>
        </div>
        @endif

        {{-- Ficha de unhas --}}
        @if($agendamento->cliente)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-hand-sparkles me-2 text-pink"></i>Ficha de unhas</h6>
            </div>
            <div class="card-body">
                @if($cliente->contraindicacoes)
                    <div class="alert alert-warning py-2 small mb-3">
                        <strong><i class="fas fa-ban me-1"></i>Contraindicações:</strong> {{ $cliente->contraindicacoes }}
                    </div>
                @endif

                <form method="POST" action="{{ route('manicure.agenda.ficha', $agendamento) }}">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="notas_unhas">Notas das unhas</label>
                            <textarea name="notas_unhas" id="notas_unhas" rows="2" maxlength="2000"
                                      class="form-control form-control-sm @error('notas_unhas') is-invalid @enderror">{{ old('notas_unhas', $cliente->notas_unhas) }}</textarea>
                            @error('notas_unhas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="cores_preferidas">Cores preferidas</label>
                            <textarea name="cores_preferidas" id="cores_preferidas" rows="2" maxlength="500"
                                      class="form-control form-control-sm @error('cores_preferidas') is-invalid @enderror">{{ old('cores_preferidas', $cliente->cores_preferidas) }}</textarea>
                            @error('cores_preferidas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="contraindicacoes">Contraindicações</label>
                            <textarea name="contraindicacoes" id="contraindicacoes" rows="2" maxlength="1000"
                                      class="form-control form-control-sm @error('contraindicacoes') is-invalid @enderror">{{ old('contraindicacoes', $cliente->contraindicacoes) }}</textarea>
                            @error('contraindicacoes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="ultima_formula">Última fórmula</label>
                            <textarea name="ultima_formula" id="ultima_formula" rows="2" maxlength="2000"
                                      class="form-control form-control-sm @error('ultima_formula') is-invalid @enderror">{{ old('ultima_formula', $cliente->ultima_formula) }}</textarea>
                            @error('ultima_formula') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check mb-2">
                                <input type="hidden" name="registrar_visita" value="0">
                                <input class="form-check-input" type="checkbox" name="registrar_visita" value="1"
                                       id="registrar_visita" {{ old('registrar_visita') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="registrar_visita">
                                    Registrar no histórico desta visita
                                </label>
                            </div>
                            <label class="form-label fw-semibold small" for="notas_visita">Nota da visita (opcional)</label>
                            <textarea name="notas_visita" id="notas_visita" rows="2" maxlength="2000"
                                      class="form-control form-control-sm @error('notas_visita') is-invalid @enderror"
                                      placeholder="O que foi feito hoje...">{{ old('notas_visita') }}</textarea>
                            @error('notas_visita') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-pink btn-sm">
                                <i class="fas fa-save me-1"></i>Salvar ficha
                            </button>
                        </div>
                    </div>
                </form>

                @if($cliente->fichaHistorico->isNotEmpty())
                    <hr class="my-3">
                    <small class="text-muted fw-semibold d-block mb-2">Histórico recente</small>
                    @foreach($cliente->fichaHistorico as $entrada)
                        <div class="small text-muted {{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}">
                            <div>{{ $entrada->created_at->format('d/m/Y H:i') }}</div>
                            @if($entrada->cores)<div>Cores: {{ $entrada->cores }}</div>@endif
                            @if($entrada->formula)<div>Fórmula: {{ $entrada->formula }}</div>@endif
                            @if($entrada->notas)<div>{{ $entrada->notas }}</div>@endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        {{-- Pagamento (se finalizado) --}}
        @if($agendamento->comanda)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-receipt me-2 text-pink"></i>Comanda #{{ $agendamento->comanda->id }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Subtotal</div>
                        <div class="fw-bold">R$ {{ number_format($agendamento->comanda->valor_servicos ?? $agendamento->valor_total, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Desconto</div>
                        <div class="fw-bold text-danger">- R$ {{ number_format($agendamento->valor_desconto, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Total Pago</div>
                        <div class="fw-bold text-success fs-5">R$ {{ number_format($agendamento->comanda->total_pago, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Forma</div>
                        <div class="fw-bold">
                            @if($agendamento->pagamentos->first())
                                {{ ucfirst($agendamento->pagamentos->first()->forma) }}
                            @else — @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Ações --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-gears me-2 text-pink"></i>Ações</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @if(in_array($agendamento->status, ['aguardando', 'confirmado']))
                    <form action="{{ route('manicure.agenda.status', $agendamento) }}" method="POST"
                          data-confirm="Iniciar atendimento?" data-confirm-message="Confirmar o início do atendimento agora?" data-confirm-type="info" data-confirm-ok="Iniciar">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="em_andamento">
                        <button type="submit" class="btn btn-pink w-100">
                            <i class="fa-solid fa-play me-1"></i>Iniciar Atendimento
                        </button>
                    </form>
                @endif

                @if($agendamento->status === 'em_andamento')
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalFinalizarShow">
                        <i class="fa-solid fa-flag-checkered me-1"></i>Finalizar Atendimento
                    </button>
                @endif

                @if($agendamento->podeSerCancelado())
                    <form action="{{ route('manicure.agenda.status', $agendamento) }}" method="POST"
                          data-confirm="Marcar como falta?" data-confirm-message="Confirmar que o cliente não compareceu?" data-confirm-type="warning" data-confirm-ok="Marcar falta">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="nao_compareceu">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fa-solid fa-ban me-1"></i>Não Compareceu
                        </button>
                    </form>
                @endif

                <a href="{{ route('manicure.agenda.index', ['data' => $agendamento->data_hora_inicio->toDateString()]) }}"
                   class="btn btn-outline-secondary">
                    <i class="fa-solid fa-calendar me-1"></i>Ver Agenda
                </a>
            </div>
        </div>

        {{-- Info Cliente --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-user me-2 text-pink"></i>Perfil do Cliente</h6>
            </div>
            <div class="card-body">
                @if($agendamento->cliente)
                    <div class="text-center mb-3">
                        <div class="avatar-circle mx-auto mb-2">
                            {{ strtoupper(substr($agendamento->cliente->nome, 0, 1)) }}
                        </div>
                        <h6 class="fw-bold mb-0">{{ $agendamento->cliente->nome }}</h6>
                    </div>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Visitas</span>
                            <strong>{{ $agendamento->cliente->total_visitas }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total gasto</span>
                            <strong class="text-pink">R$ {{ number_format($agendamento->cliente->total_gasto, 2, ',', '.') }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Pontos fidelidade</span>
                            <strong>{{ $agendamento->cliente->pontos_fidelidade }} pts</strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 {{ $temAlergia || $ehRisco ? 'border-bottom' : '' }}">
                            <span class="text-muted">Faltas</span>
                            <strong class="{{ $ehRisco ? 'text-warning' : '' }}">{{ $agendamento->cliente->total_faltas }}</strong>
                        </li>
                        @if($temAlergia)
                            <li class="py-2">
                                <span class="text-muted d-block mb-1">Alergias</span>
                                <span class="badge bg-red-light text-danger text-wrap text-start w-100">
                                    <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                                    {{ $agendamento->cliente->alergias }}
                                </span>
                            </li>
                        @endif
                        @if($ehRisco)
                            <li class="pt-2">
                                <span class="badge bg-yellow-light text-dark text-wrap text-start w-100">
                                    <i class="fas fa-user-clock me-1" aria-hidden="true"></i>
                                    Risco de não comparecimento
                                </span>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="text-muted small mb-0">
                        <i class="fa-solid fa-user-slash me-1"></i>
                        Cliente avulso: {{ $agendamento->cliente_nome ?? '—' }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($agendamento->status === 'em_andamento')
<div class="modal fade" id="modalFinalizarShow" tabindex="-1" aria-labelledby="modalFinalizarShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFinalizarShowLabel">Finalizar Atendimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="{{ route('manicure.agenda.status', $agendamento) }}" class="agenda-status-form">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="concluido">
                <div class="modal-body">
                    <p class="mb-1"><strong>Cliente:</strong> {{ $agendamento->nome_cliente_exibido }}</p>
                    <p class="mb-1"><strong>Serviços:</strong> {{ $agendamento->servicos->pluck('nome')->implode(', ') }}</p>
                    <p class="mb-3"><strong>Valor:</strong> R$ {{ number_format($agendamento->valor_total, 2, ',', '.') }}</p>
                    @if($temAlergia)
                        <div class="alert alert-danger py-2 small">
                            <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                            <strong>Alergias:</strong> {{ $cliente->alergias }}
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="formaPagamentoShow">Forma de Pagamento</label>
                        <select name="forma_pagamento" id="formaPagamentoShow" class="form-select" required>
                            @foreach(\App\Models\Pagamento::FORMAS_LABELS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="gorjetaShow">Gorjeta (opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="gorjeta" id="gorjetaShow" class="form-control"
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
@endsection

@push('styles')
<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--pink);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Salvando…';
    });
});
</script>
@endpush
