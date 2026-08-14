/**
 * Shared slot picker for booking / reschedule flows.
 * Renders chip UI, optionally holds a slot via POST /api/slots/hold.
 *
 * Usage (after Vite bundle loads):
 *   const picker = window.createSlotPicker({ getManicureId, getDuracao, hold: true });
 *   // call picker.load() when manicure/services change
 */
export function createSlotPicker(opts = {}) {
    const inputData = resolveEl(opts.inputData, 'inputData');
    const slotsGrid = resolveEl(opts.slotsGrid, 'slotsGrid');
    const slotsHint = resolveEl(opts.slotsHint, 'slotsHint');
    const skeletonHora = resolveEl(opts.skeletonHora, 'skeletonHora');
    const dataHoraInicio = resolveEl(opts.dataHoraInicio, 'dataHoraInicio');

    if (!inputData || !slotsGrid || !dataHoraInicio) {
        console.warn('[booking-slots] missing required elements');
        return null;
    }

    const emptyHint = opts.emptyHint
        ?? 'Selecione manicure, serviços e data para ver os horários.';
    const hold = !!opts.hold;
    const onChange = typeof opts.onChange === 'function' ? opts.onChange : null;
    const onSlotsLoaded = typeof opts.onSlotsLoaded === 'function' ? opts.onSlotsLoaded : null;

    let holdToken = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function resolveValue(fnOrVal) {
        return typeof fnOrVal === 'function' ? fnOrVal() : fnOrVal;
    }

    function getManicureId() {
        return resolveValue(opts.getManicureId ?? opts.manicureId);
    }

    function getDuracao() {
        return Number(resolveValue(opts.getDuracao ?? opts.duracao) || 0);
    }

    function setValue(dt) {
        dataHoraInicio.value = dt || '';
        onChange?.(dt || null);
    }

    function resetSlots(msg = emptyHint) {
        slotsGrid.innerHTML = '';
        slotsGrid.classList.add('d-none');
        setValue('');
        if (slotsHint) {
            slotsHint.textContent = msg;
            slotsHint.classList.remove('d-none');
        }
    }

    async function load() {
        const manicureId = getManicureId();
        const data = inputData.value;
        const duracao = getDuracao();

        if (!manicureId || !data || !duracao) {
            resetSlots(emptyHint);
            return;
        }

        if (slotsHint) slotsHint.classList.add('d-none');
        slotsGrid.classList.add('d-none');
        skeletonHora?.classList.remove('d-none');
        setValue('');

        try {
            const r = await fetch(
                `/api/slots?manicure_id=${encodeURIComponent(manicureId)}&data=${encodeURIComponent(data)}&duracao=${encodeURIComponent(duracao)}`
            );
            const j = await r.json();
            skeletonHora?.classList.add('d-none');

            if (j.slots?.length) {
                slotsGrid.innerHTML = j.slots.map((s) =>
                    `<button type="button" class="slot-chip" role="option" aria-selected="false" data-dt="${s.datetime}">${s.hora}</button>`
                ).join('');
                slotsGrid.classList.remove('d-none');
                onSlotsLoaded?.(j.slots, { manicureId, data, duracao });
            } else {
                resetSlots('Sem horários disponíveis nesta data. Tente outro dia.');
                onSlotsLoaded?.([], { manicureId, data, duracao });
            }
        } catch (_) {
            skeletonHora?.classList.add('d-none');
            resetSlots('Erro ao buscar horários. Tente novamente.');
            onSlotsLoaded?.(null, { manicureId, data, duracao });
        }
    }

    async function selectChip(chip) {
        if (!chip || chip.disabled) return;

        if (hold) {
            try {
                const r = await fetch('/api/slots/hold', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
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
            } catch (_) {
                /* offline: continue without hold */
            }
        }

        slotsGrid.querySelectorAll('.slot-chip').forEach((c) => {
            c.classList.remove('selected');
            c.setAttribute('aria-selected', 'false');
        });
        chip.classList.add('selected');
        chip.setAttribute('aria-selected', 'true');
        setValue(chip.dataset.dt);
    }

    slotsGrid.addEventListener('click', (e) => {
        const chip = e.target.closest('.slot-chip');
        if (chip) selectChip(chip);
    });

    slotsGrid.addEventListener('keydown', (e) => {
        const chip = e.target.closest('.slot-chip');
        if (!chip) return;
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectChip(chip);
        }
    });

    inputData.addEventListener('change', load);

    function selectDatetime(dt) {
        if (!dt) return false;
        const chip = slotsGrid.querySelector(`.slot-chip[data-dt="${dt}"]`);
        if (!chip) return false;
        slotsGrid.querySelectorAll('.slot-chip').forEach((c) => {
            c.classList.remove('selected');
            c.setAttribute('aria-selected', 'false');
        });
        chip.classList.add('selected');
        chip.setAttribute('aria-selected', 'true');
        setValue(dt);
        return true;
    }

    return {
        load,
        reset: resetSlots,
        selectDatetime,
        getValue: () => dataHoraInicio.value,
        get inputData() {
            return inputData;
        },
    };
}

function resolveEl(value, id) {
    if (value instanceof Element) return value;
    if (typeof value === 'string') return document.querySelector(value);
    return document.getElementById(id);
}

window.createSlotPicker = createSlotPicker;
