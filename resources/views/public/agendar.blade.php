<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agendar em {{ $salao->nome }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="app-env" content="{{ app()->environment() }}">
    <x-theme-vars />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-body">

<x-skip-link />
<x-public-navbar :back-url="route('public.salao', $salao->slug)" />

<main id="mainContent" class="container py-4" tabindex="-1">
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

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @guest
                <div class="alert alert-light border d-flex flex-wrap align-items-center gap-3 mb-4">
                    <div class="flex-grow-1">
                        <strong>Agende sem criar conta</strong>
                        <div class="small text-muted">Informe nome e telefone no final. Já tem conta?
                            <a href="{{ route('login') }}?salao={{ $salao->slug }}">Entrar</a>
                        </div>
                    </div>
                </div>
            @endguest

            <form method="POST"
                  action="{{ auth()->check() ? route('cliente.agendamentos.store') : route('public.agendar.store', $salao) }}"
                  id="formPublicoAgendar">
                @csrf
                <x-honeypot />
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
                                        <input type="radio" name="manicure_id" value="{{ $m->id }}"
                                               class="visually-hidden manicure-radio" required
                                               @checked(old('manicure_id') == $m->id)>
                                        <div class="manicure-card p-3 text-center @if(old('manicure_id') == $m->id) selected @endif">
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
                                               class="servico-check visually-hidden"
                                               @checked(collect(old('servico_ids', []))->contains($s->id))>
                                        <div class="servico-option p-3 @if(collect(old('servico_ids', []))->contains($s->id)) selected @endif">
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
                        @include('agendamentos._slots_picker', [
                            'dateLabel' => 'Data',
                            'timeLabel' => 'Horário',
                            'hint' => 'Selecione manicure, serviços e data para ver os horários.',
                        ])
                    </div>
                </div>

                @guest
                {{-- Step 4: Contato guest --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <span class="badge bg-pink rounded-circle step-badge">4</span>
                        <h6 class="mb-0 fw-bold">Seus dados</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="guestNome">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="guestNome" class="form-control form-control-lg"
                                       value="{{ old('nome') }}" required maxlength="255" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="guestTelefone">Telefone / WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" name="telefone" id="guestTelefone" class="form-control form-control-lg"
                                       value="{{ old('telefone') }}" required maxlength="20" autocomplete="tel"
                                       placeholder="(11) 99999-9999">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="guestEmail">E-mail <small class="text-muted">(opcional)</small></label>
                                <input type="email" name="email" id="guestEmail" class="form-control form-control-lg"
                                       value="{{ old('email') }}" maxlength="255" autocomplete="email"
                                       placeholder="Para receber a confirmação">
                            </div>
                        </div>
                    </div>
                </div>
                @endguest

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
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2" placeholder="Alguma alergia ou preferência específica?">{{ old('observacoes') }}</textarea>
                </div>

                <div class="booking-submit">
                    <button type="submit" class="btn btn-pink btn-lg w-100" id="btnSubmit" disabled>
                        <i class="fas fa-calendar-check me-2" aria-hidden="true"></i> Confirmar agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<x-public-footer compact />

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnSubmit = document.getElementById('btnSubmit');
    const resumoCard = document.getElementById('resumoCard');
    const isGuest = {{ auth()->guest() ? 'true' : 'false' }};

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

    function guestDadosOk() {
        if (!isGuest) return true;
        const nome = document.getElementById('guestNome')?.value.trim();
        const tel = (document.getElementById('guestTelefone')?.value || '').replace(/\D/g, '');
        return !!(nome && tel.length >= 10);
    }

    function atualizarResumo(duracao) {
        if (!(duracao > 0)) {
            resumoCard.classList.add('d-none');
            return;
        }
        const v = getValor(), h = Math.floor(duracao / 60), mm = duracao % 60;
        resumoCard.classList.remove('d-none');
        document.getElementById('resumoDuracao').textContent = h > 0 ? `${h}h ${mm}min` : `${mm}min`;
        document.getElementById('resumoValor').textContent = 'R$ ' + v.toFixed(2).replace('.', ',');
    }

    function verificarBotao() {
        btnSubmit.disabled = !(getDuracao() > 0 && getManicureId()
            && picker?.inputData?.value && picker?.getValue() && guestDadosOk());
    }

    const picker = window.createSlotPicker({
        getManicureId,
        getDuracao,
        hold: true,
        emptyHint: 'Selecione manicure, serviços e data para ver os horários.',
        onChange: verificarBotao,
        onSlotsLoaded: (slots, ctx) => {
            if (slots?.length) atualizarResumo(ctx.duracao);
            else resumoCard.classList.add('d-none');
            verificarBotao();
        },
    });

    document.querySelectorAll('.servico-check').forEach((c) => {
        c.addEventListener('change', function () {
            this.closest('label').querySelector('.servico-option').classList.toggle('selected', this.checked);
            picker?.load();
        });
    });
    document.querySelectorAll('.manicure-radio').forEach((r) => {
        r.addEventListener('change', function () {
            document.querySelectorAll('.manicure-card').forEach((card) => card.classList.remove('selected'));
            this.closest('label').querySelector('.manicure-card').classList.add('selected');
            picker?.load();
        });
    });

    ['guestNome', 'guestTelefone'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', verificarBotao);
    });

    // Restaura data a partir de old(data_hora_inicio) e recarrega chips
    const oldDt = document.getElementById('dataHoraInicio')?.value;
    if (oldDt && picker?.inputData) {
        const dt = oldDt.replace(' ', 'T');
        if (dt.length >= 10) {
            picker.inputData.value = dt.slice(0, 10);
            picker.load().then(() => {
                picker.selectDatetime(oldDt);
            });
        }
    }

    // Resumo imediato se serviços já vinham marcados (old input)
    if (getDuracao() > 0) atualizarResumo(getDuracao());
    verificarBotao();
});
</script>
</body>
</html>
