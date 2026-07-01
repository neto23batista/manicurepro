<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agendar em {{ $salao->nome }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ec4899">
    <meta name="app-env" content="{{ app()->environment() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-body">

<x-public-navbar :back-url="route('public.salao', $salao->slug)" />

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- Cabeçalho do salão --}}
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="p-4 d-flex align-items-center gap-3 salao-summary-bg">
                    <img src="{{ $salao->logo_url }}" alt="{{ $salao->nome }}" class="rounded-3 shadow-sm salao-summary-img">
                    <div>
                        <h4 class="mb-0 fw-bold">{{ $salao->nome }}</h4>
                        <p class="mb-0 small text-muted">{{ $salao->endereco_completo }}</p>
                    </div>
                </div>
            </div>

            @guest
                <div class="alert alert-pink d-flex align-items-center gap-3 mb-4">
                    <i class="fas fa-lock fs-3" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <strong>É rápido!</strong> Para agendar você precisa de uma conta.
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('login') }}?salao={{ $salao->slug }}" class="btn btn-outline-pink btn-sm">Entrar</a>
                        <a href="{{ route('register') }}?salao={{ $salao->id }}" class="btn btn-pink btn-sm">Criar conta</a>
                    </div>
                </div>

                <div class="text-center my-5">
                    <i class="fas fa-calendar-heart text-pink fs-1 opacity-50 mb-3" aria-hidden="true"></i>
                    <h4 class="fw-bold">Cadastre-se em 30 segundos</h4>
                    <p class="text-muted">Acesso gratuito a todos os recursos, com programa de fidelidade.</p>
                    <a href="{{ route('register') }}?salao={{ $salao->id }}" class="btn btn-pink btn-lg px-5">
                        <i class="fas fa-rocket me-2" aria-hidden="true"></i> Começar agora
                    </a>
                </div>
            @else
            <form method="POST" action="{{ route('cliente.agendamentos.store') }}" id="formPublicoAgendar">
                @csrf
                <input type="hidden" name="salao_id" value="{{ $salao->id }}">

                {{-- Step 1: Manicure --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <span class="badge bg-pink rounded-circle step-badge">1</span>
                        <h6 class="mb-0 fw-bold">Escolha a manicure</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($salao->manicures as $m)
                                <div class="col-md-4 col-6">
                                    <label class="w-100 cursor-pointer">
                                        <input type="radio" name="manicure_id" value="{{ $m->id }}" class="visually-hidden manicure-radio" required>
                                        <div class="manicure-card p-3 text-center">
                                            <img src="{{ $m->foto_url }}" alt="{{ $m->nome }}" class="rounded-circle mb-2 manicure-photo">
                                            <div class="fw-semibold">{{ $m->nome }}</div>
                                            @if($m->nota_media > 0)
                                                <small class="text-warning">★ {{ $m->nota_media }}</small>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Step 2: Serviços --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <span class="badge bg-pink rounded-circle step-badge">2</span>
                        <h6 class="mb-0 fw-bold">Selecione os serviços</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach($servicos as $s)
                                <div class="col-md-6">
                                    <label class="w-100 cursor-pointer">
                                        <input type="checkbox" name="servico_ids[]" value="{{ $s->id }}"
                                               data-preco="{{ $s->preco }}" data-duracao="{{ $s->duracao }}"
                                               class="servico-check visually-hidden">
                                        <div class="servico-option p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-semibold">{{ $s->nome }}</div>
                                                    <small class="text-muted"><i class="fas fa-clock me-1" aria-hidden="true"></i>{{ $s->duracao_formatada }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-gradient">R$ {{ number_format($s->preco, 2, ',', '.') }}</div>
                                                    @if($s->combo)
                                                        <span class="badge bg-warning text-white">Combo</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Step 3: Data/Hora --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <span class="badge bg-pink rounded-circle step-badge">3</span>
                        <h6 class="mb-0 fw-bold">Data & horário</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="inputData">Data</label>
                                <input type="date" id="inputData" class="form-control form-control-lg"
                                       min="{{ today()->addDay()->toDateString() }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Horário</label>
                                <p class="text-muted small mb-2" id="slotsHint">Selecione manicure, serviços e data para ver os horários.</p>
                                <div id="skeletonHora" class="d-none">
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                    <span class="skeleton-slot">--:--</span>
                                </div>
                                <div class="slots-grid d-none" id="slotsGrid" role="group" aria-label="Horários disponíveis"></div>
                                <input type="hidden" name="data_hora_inicio" id="dataHoraInicio">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resumo --}}
                <div class="card border-0 mb-4 d-none resumo-card" id="resumoCard">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-white mb-3"><i class="fas fa-receipt me-2" aria-hidden="true"></i>Resumo do agendamento</h6>
                        <div class="row text-center">
                            <div class="col">
                                <small class="opacity-75 d-block">Duração</small>
                                <div class="fw-bold fs-5" id="resumoDuracao">-</div>
                            </div>
                            <div class="col">
                                <small class="opacity-75 d-block">Total</small>
                                <div class="fw-bold fs-4" id="resumoValor">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Observações --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="observacoes">Observações <small class="text-muted">(opcional)</small></label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2" placeholder="Alguma alergia ou preferência específica?"></textarea>
                </div>

                <div class="booking-submit">
                    <button type="submit" class="btn btn-pink btn-lg w-100" id="btnSubmit" disabled>
                        <i class="fas fa-calendar-check me-2" aria-hidden="true"></i> Confirmar agendamento
                    </button>
                </div>
            </form>
            @endguest
        </div>
    </div>
</div>

<x-public-footer compact />

@auth
<script>
const btnSubmit = document.getElementById('btnSubmit');
const inputData = document.getElementById('inputData');
const slotsGrid = document.getElementById('slotsGrid');
const slotsHint = document.getElementById('slotsHint');
const skeletonHora = document.getElementById('skeletonHora');
const dataHoraInicio = document.getElementById('dataHoraInicio');
const resumoCard = document.getElementById('resumoCard');

function getDuracao() { return [...document.querySelectorAll('.servico-check:checked')].reduce((s,c)=>s+parseInt(c.dataset.duracao),0); }
function getValor()   { return [...document.querySelectorAll('.servico-check:checked')].reduce((s,c)=>s+parseFloat(c.dataset.preco),0); }
function getManicureId() { const r=document.querySelector('.manicure-radio:checked'); return r?r.value:null; }

function resetSlots(msg) {
    slotsGrid.innerHTML = '';
    slotsGrid.classList.add('d-none');
    dataHoraInicio.value = '';
    slotsHint.textContent = msg;
    slotsHint.classList.remove('d-none');
}

async function carregarSlots() {
    const m=getManicureId(), d=inputData.value, dur=getDuracao();
    if (!m || !d || !dur) {
        resetSlots('Selecione manicure, serviços e data para ver os horários.');
        verificarBotao(); return;
    }
    slotsHint.classList.add('d-none');
    slotsGrid.classList.add('d-none');
    skeletonHora.classList.remove('d-none');

    try {
        const r=await fetch(`/api/slots?manicure_id=${m}&data=${d}&duracao=${dur}`);
        const j=await r.json();
        skeletonHora.classList.add('d-none');
        if (j.slots?.length) {
            slotsGrid.innerHTML = j.slots.map(s=>
                `<button type="button" class="slot-chip" data-dt="${s.datetime}">${s.hora}</button>`
            ).join('');
            slotsGrid.classList.remove('d-none');

            const v=getValor(), h=Math.floor(dur/60), mm=dur%60;
            resumoCard.classList.remove('d-none');
            document.getElementById('resumoDuracao').textContent = h>0?`${h}h ${mm}min`:`${mm}min`;
            document.getElementById('resumoValor').textContent = 'R$ '+v.toFixed(2).replace('.',',');
        } else {
            resetSlots('Sem horários disponíveis nesta data. Tente outro dia.');
        }
    } catch(e) {
        skeletonHora.classList.add('d-none');
        resetSlots('Erro ao buscar horários. Tente novamente.');
    }
    verificarBotao();
}

function verificarBotao() {
    btnSubmit.disabled = !(getDuracao()>0 && getManicureId() && inputData.value && dataHoraInicio.value);
}

// Seleção do chip de horário — reserva temporária para evitar choque de horário
let holdToken = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

slotsGrid.addEventListener('click', async function(e) {
    const chip = e.target.closest('.slot-chip');
    if (!chip || chip.disabled) return;

    try {
        const r = await fetch('/api/slots/hold', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                manicure_id: getManicureId(),
                data_hora_inicio: chip.dataset.dt,
                duracao: getDuracao(),
                token: holdToken,
            }),
        });
        if (r.status === 409) {
            chip.disabled = true;
            chip.style.opacity = '0.4';
            chip.title = 'Acabou de ser reservado';
            window.showToast?.('Esse horário acabou de ser reservado. Escolha outro.', 'warning');
            return;
        }
        const j = await r.json();
        holdToken = j.token || holdToken;
    } catch (_) { /* sem rede: segue sem reserva */ }

    slotsGrid.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('selected'));
    chip.classList.add('selected');
    dataHoraInicio.value = chip.dataset.dt;
    verificarBotao();
});

document.querySelectorAll('.servico-check').forEach(c=>{
    c.addEventListener('change', function() {
        this.closest('label').querySelector('.servico-option').classList.toggle('selected', this.checked);
        carregarSlots();
    });
});
document.querySelectorAll('.manicure-radio').forEach(r=>{
    r.addEventListener('change', function() {
        document.querySelectorAll('.manicure-card').forEach(c=>c.classList.remove('selected'));
        this.closest('label').querySelector('.manicure-card').classList.add('selected');
        carregarSlots();
    });
});
inputData.addEventListener('change', carregarSlots);
</script>
@endauth
</body>
</html>
