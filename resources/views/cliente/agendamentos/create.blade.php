@extends('layouts.app')

@section('title', 'Novo Agendamento')
@section('page-title', 'Fazer Agendamento')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-plus text-pink me-2"></i> Agendar Atendimento
                </h5>
            </div>
            <div class="card-body">
                @if(!$salao)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Agendamento indisponível no momento.
                    </div>
                @else
                <form method="POST" action="{{ route('cliente.agendamentos.store') }}" id="formAgendamento">
                    @csrf

                    <input type="hidden" name="salao_id" value="{{ $salao->id }}">
                    <div class="alert alert-pink mb-4">
                        <i class="fas fa-store me-2"></i> <strong>{{ $salao->nome }}</strong>
                        <br><small>{{ $salao->endereco_completo }}</small>
                    </div>

                    {{-- Passo 1: Manicure --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">1. Escolha a Manicure</h6>
                        <div class="row g-3" id="listaManicures">
                            @foreach($manicures as $m)
                                <div class="col-md-4">
                                    <label class="cursor-pointer w-100">
                                        <input type="radio" name="manicure_id" value="{{ $m->id }}"
                                               class="visually-hidden manicure-radio" required>
                                        <div class="manicure-card p-3 border rounded text-center">
                                            <img src="{{ $m->foto_url }}" width="56" height="56" class="rounded-circle mb-2">
                                            <div class="fw-semibold">{{ $m->nome }}</div>
                                            @if($m->nota_media > 0)
                                                <small class="text-warning">★ {{ $m->nota_media }}</small>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('manicure_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Passo 3: Serviços --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">2. Escolha os Serviços</h6>
                        <div class="row g-2" id="listaServicos">
                            @foreach($servicos as $s)
                                <div class="col-md-4">
                                    <label class="cursor-pointer w-100">
                                        <input type="checkbox" name="servico_ids[]" value="{{ $s->id }}"
                                               data-preco="{{ $s->preco }}" data-duracao="{{ $s->duracao }}"
                                               class="servico-check visually-hidden">
                                        <div class="servico-option p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="fw-semibold">{{ $s->nome }}</div>
                                                @if($s->combo)
                                                    <span class="badge bg-pink">Combo</span>
                                                @endif
                                            </div>
                                            <div class="text-pink fw-bold">{{ $s->preco_formatado }}</div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>{{ $s->duracao_formatada }}
                                            </small>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('servico_ids')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Passo 4: Data e Hora --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">3. Data e Horário</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Selecione a Data</label>
                                <input type="date" name="data" id="inputData" class="form-control"
                                       min="{{ today()->addDay()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Horário disponível</label>
                                <select name="hora" id="selectHora" class="form-select" disabled required>
                                    <option value="">Primeiro, selecione manicure, serviço e data</option>
                                </select>
                                <div id="skeletonHora" class="d-none mt-2">
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="data_hora_inicio" id="dataHoraInicio">
                    </div>

                    {{-- Resumo --}}
                    <div class="resumo-card p-3 rounded bg-light mb-4 d-none" id="resumoCard">
                        <h6 class="fw-bold mb-3 text-pink">Resumo do Agendamento</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small">Duração estimada</span>
                                <div class="fw-semibold" id="resumoDuracao">-</div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small">Total</span>
                                <div class="fw-bold text-pink fs-5" id="resumoValor">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações (opcional)</label>
                        <textarea name="observacoes" class="form-control" rows="2"
                                  placeholder="Alguma alergia, preferência ou observação?">{{ old('observacoes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink btn-lg" id="btnSubmit" disabled>
                            <i class="fas fa-calendar-check me-2"></i> Confirmar Agendamento
                        </button>
                        <a href="{{ route('cliente.dashboard') }}" class="btn btn-outline-secondary btn-lg">
                            Cancelar
                        </a>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const btnSubmit = document.getElementById('btnSubmit');
const inputData = document.getElementById('inputData');
const selectHora = document.getElementById('selectHora');
const dataHoraInicio = document.getElementById('dataHoraInicio');
const resumoCard = document.getElementById('resumoCard');

function getDuracao() {
    return [...document.querySelectorAll('.servico-check:checked')]
        .reduce((s, c) => s + parseInt(c.dataset.duracao), 0);
}

function getValor() {
    return [...document.querySelectorAll('.servico-check:checked')]
        .reduce((s, c) => s + parseFloat(c.dataset.preco), 0);
}

function getManicureId() {
    const r = document.querySelector('.manicure-radio:checked');
    return r ? r.value : null;
}

function atualizarResumo() {
    const d = getDuracao(), v = getValor();
    if (d > 0) {
        resumoCard.classList.remove('d-none');
        const h = Math.floor(d/60), m = d%60;
        document.getElementById('resumoDuracao').textContent = h > 0 ? `${h}h ${m}min` : `${m}min`;
        document.getElementById('resumoValor').textContent = 'R$ ' + v.toFixed(2).replace('.', ',');
    } else {
        resumoCard.classList.add('d-none');
    }
}

async function carregarSlots() {
    const manicureId = getManicureId();
    const data = inputData.value;
    const duracao = getDuracao();

    if (!manicureId || !data || duracao === 0) {
        selectHora.innerHTML = '<option value="">Selecione manicure, serviços e data</option>';
        selectHora.disabled = true;
        verificarBotao();
        return;
    }

    const sk = document.getElementById('skeletonHora');
    selectHora.classList.add('d-none');
    sk?.classList.remove('d-none');
    selectHora.disabled = true;
    selectHora.innerHTML = '<option value="">Buscando horários...</option>';

    try {
        const resp = await fetch(`/api/slots?manicure_id=${manicureId}&data=${data}&duracao=${duracao}`);
        const json = await resp.json();
        if (json.slots && json.slots.length > 0) {
            selectHora.innerHTML = '<option value="">Escolha um horário</option>' +
                json.slots.map(s => `<option value="${s.datetime}">${s.hora}</option>`).join('');
            selectHora.disabled = false;
        } else {
            selectHora.innerHTML = '<option value="">Sem horários disponíveis nesta data</option>';
        }
    } catch {
        selectHora.innerHTML = '<option value="">Erro ao carregar horários</option>';
    }
    sk?.classList.add('d-none');
    selectHora.classList.remove('d-none');
    verificarBotao();
}

function verificarBotao() {
    const temServico = getDuracao() > 0;
    const temManicure = !!getManicureId();
    const temData = !!inputData.value;
    const temHora = !!selectHora.value;
    btnSubmit.disabled = !(temServico && temManicure && temData && temHora);
}

document.querySelectorAll('.servico-check').forEach(c => {
    c.addEventListener('change', function() {
        this.closest('label').querySelector('.servico-option').classList.toggle('selected', this.checked);
        atualizarResumo();
        carregarSlots();
    });
});

document.querySelectorAll('.manicure-radio').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.manicure-card').forEach(c => c.classList.remove('selected'));
        this.closest('label').querySelector('.manicure-card').classList.add('selected');
        carregarSlots();
    });
});

inputData.addEventListener('change', carregarSlots);

selectHora.addEventListener('change', function() {
    dataHoraInicio.value = this.value;
    verificarBotao();
});
</script>
@endpush
