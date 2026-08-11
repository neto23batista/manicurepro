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
            @include('agendamentos._slots_picker', [
                'dateLabel' => 'Nova data',
                'timeLabel' => 'Novo horário',
                'hint' => 'Escolha uma data para ver os horários disponíveis.',
            ])

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
document.addEventListener('DOMContentLoaded', function () {
    const btnSubmit = document.getElementById('btnSubmit');

    window.createSlotPicker({
        manicureId: {{ $agendamento->manicure_id }},
        duracao: {{ $duracao }},
        emptyHint: 'Escolha uma data para ver os horários disponíveis.',
        onChange: (dt) => { btnSubmit.disabled = !dt; },
    });
});
</script>
@endpush
