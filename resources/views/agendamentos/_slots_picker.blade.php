{{--
  Shared date + slot-chip picker.
  Vars (all optional):
    $dateLabel, $timeLabel, $hint, $dateColClass, $minDate, $dateId, $dataHoraInicioValue
--}}
@php
    $dateLabel = $dateLabel ?? 'Data';
    $timeLabel = $timeLabel ?? 'Horário';
    $hint = $hint ?? 'Selecione manicure, serviços e data para ver os horários.';
    $dateColClass = $dateColClass ?? 'col-md-5';
    $minDate = $minDate ?? today()->addDay()->toDateString();
    $dateId = $dateId ?? 'inputData';
    $dataHoraInicioValue = $dataHoraInicioValue ?? old('data_hora_inicio', '');
@endphp

<div class="row g-3" data-slots-picker>
    <div class="{{ $dateColClass }}">
        <label class="form-label" for="{{ $dateId }}">{{ $dateLabel }}</label>
        <input type="date" id="{{ $dateId }}" class="form-control form-control-lg"
               min="{{ $minDate }}" required>
    </div>
    <div class="col-12">
        <label class="form-label d-block">{{ $timeLabel }}</label>
        <p class="text-muted small mb-2" id="slotsHint" role="status" aria-live="polite">{{ $hint }}</p>
        <div id="skeletonHora" class="d-none" aria-hidden="true" aria-busy="true">
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
            <span class="skeleton-slot">--:--</span>
        </div>
        <div class="slots-grid d-none" id="slotsGrid" role="listbox" aria-label="Horários disponíveis"></div>
        <input type="hidden" name="data_hora_inicio" id="dataHoraInicio" value="{{ $dataHoraInicioValue }}" aria-required="true">
    </div>
</div>
