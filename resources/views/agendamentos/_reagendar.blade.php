@php
    $duracao = (int) $agendamento->servicos->sum(fn($s) => $s->pivot->duracao);
    if ($duracao <= 0) {
        $duracao = (int) $agendamento->data_hora_inicio->diffInMinutes($agendamento->data_hora_fim);
    }
@endphp

@if ($errors->any())
    <div class="alert alert-danger alert-permanent">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3 bg-pink-light">
            <i class="fas fa-clock-rotate-left fs-3 text-pink" aria-hidden="true"></i>
            <div>
                <div class="small text-muted">Horário atual</div>
                <div class="fw-bold">{{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}</div>
                <div class="small text-muted">
                    {{ $agendamento->manicure->nome }} ·
                    {{ $agendamento->servicos->pluck('nome')->implode(', ') }}
                    ({{ $duracao }} min)
                </div>
            </div>
        </div>

        <form method="POST" action="{{ $action }}" id="formReagendar">
            @csrf
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="inputData">Nova data</label>
                    <input type="date" id="inputData" class="form-control form-control-lg"
                           min="{{ today()->addDay()->toDateString() }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label d-block">Novo horário</label>
                    <p class="text-muted small mb-2" id="slotsHint">Escolha uma data para ver os horários disponíveis.</p>
                    <div id="skeletonHora" class="d-none">
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

            <div class="booking-submit mt-4 d-flex gap-2">
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-pink btn-lg flex-grow-1" id="btnSubmit" disabled>
                    <i class="fas fa-calendar-check me-2" aria-hidden="true"></i> Confirmar remarcação
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const manicureId = {{ $agendamento->manicure_id }};
    const duracao = {{ $duracao }};
    const inputData = document.getElementById('inputData');
    const slotsGrid = document.getElementById('slotsGrid');
    const slotsHint = document.getElementById('slotsHint');
    const skeletonHora = document.getElementById('skeletonHora');
    const dataHoraInicio = document.getElementById('dataHoraInicio');
    const btnSubmit = document.getElementById('btnSubmit');

    function reset(msg) {
        slotsGrid.innerHTML = '';
        slotsGrid.classList.add('d-none');
        dataHoraInicio.value = '';
        slotsHint.textContent = msg;
        slotsHint.classList.remove('d-none');
        btnSubmit.disabled = true;
    }

    async function carregar() {
        const d = inputData.value;
        if (!d) { reset('Escolha uma data para ver os horários disponíveis.'); return; }
        slotsHint.classList.add('d-none');
        slotsGrid.classList.add('d-none');
        skeletonHora.classList.remove('d-none');
        try {
            const r = await fetch(`/api/slots?manicure_id=${manicureId}&data=${d}&duracao=${duracao}`);
            const j = await r.json();
            skeletonHora.classList.add('d-none');
            if (j.slots?.length) {
                slotsGrid.innerHTML = j.slots.map(s =>
                    `<button type="button" class="slot-chip" data-dt="${s.datetime}">${s.hora}</button>`
                ).join('');
                slotsGrid.classList.remove('d-none');
            } else {
                reset('Sem horários disponíveis nesta data. Tente outro dia.');
            }
        } catch (e) {
            skeletonHora.classList.add('d-none');
            reset('Erro ao buscar horários. Tente novamente.');
        }
    }

    slotsGrid.addEventListener('click', function (e) {
        const chip = e.target.closest('.slot-chip');
        if (!chip) return;
        slotsGrid.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('selected'));
        chip.classList.add('selected');
        dataHoraInicio.value = chip.dataset.dt;
        btnSubmit.disabled = false;
    });

    inputData.addEventListener('change', carregar);
})();
</script>
@endpush
