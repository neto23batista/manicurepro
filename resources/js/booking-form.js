/**
 * Shared booking form wiring (guest / cliente / dono / reagendar).
 * Builds on createSlotPicker — keeps AgendaService contract intact.
 *
 * Usage:
 *   window.initBookingForm({ mode: 'cliente' | 'guest' | 'dono' | 'reagendar', ... })
 */
import { createSlotPicker } from './booking-slots.js';

export function initBookingForm(opts = {}) {
    const mode = opts.mode || 'cliente';
    const btnSubmit = document.getElementById(opts.submitId || 'btnSubmit');
    const resumoCard = document.getElementById(opts.resumoId || 'resumoCard')
        || document.getElementById('resumo');
    const isGuest = mode === 'guest';
    const isDono = mode === 'dono';
    const isReagendar = mode === 'reagendar';

    const encaixeCheck = document.getElementById('encaixeCheck') || document.getElementById('encaixe');
    const encaixeBox = document.getElementById('encaixeHorario') || document.getElementById('encaixeDatetimeWrap');
    const encaixeDatetime = document.getElementById('encaixeDatetime');
    const dataHoraHidden = document.getElementById('dataHoraInicio');
    const selectManicure = document.getElementById('selectManicure') || document.getElementById('manicure_id');
    const slotsPickerRoot = document.querySelector('[data-slots-picker]');

    function syncVariacao(sel) {
        const check = document.querySelector(`.servico-check[data-servico-id="${sel.dataset.servicoId}"]`);
        if (!check) return;
        const opt = sel.selectedOptions[0];
        if (!opt) return;
        check.dataset.duracao = opt.dataset.duracao || sel.dataset.baseDuracao || check.dataset.duracao;
        check.dataset.preco = opt.dataset.preco || sel.dataset.basePreco || check.dataset.preco;

        const card = check.closest('label')?.querySelector('.servico-option');
        if (card) {
            const precoEl = card.querySelector('.servico-preco-label');
            const durEl = card.querySelector('.servico-duracao-label');
            const p = parseFloat(check.dataset.preco);
            const d = parseInt(check.dataset.duracao, 10);
            if (precoEl && !Number.isNaN(p)) {
                precoEl.textContent = 'R$ ' + p.toFixed(2).replace('.', ',');
            }
            if (durEl && !Number.isNaN(d)) {
                const h = Math.floor(d / 60);
                const m = d % 60;
                durEl.textContent = h > 0 ? `${h}h${m > 0 ? ' ' + m + 'min' : ''}` : `${m}min`;
            }
        }
    }

    function getDuracao() {
        if (isReagendar) return Number(opts.duracao || 0);
        return [...document.querySelectorAll('.servico-check:checked')]
            .reduce((s, c) => s + parseInt(c.dataset.duracao || '0', 10), 0);
    }

    function getValor() {
        return [...document.querySelectorAll('.servico-check:checked')]
            .reduce((s, c) => s + parseFloat(c.dataset.preco || '0'), 0);
    }

    function getManicureId() {
        if (isReagendar) return opts.manicureId;
        if (selectManicure) return selectManicure.value || null;
        const r = document.querySelector('.manicure-radio:checked');
        return r ? r.value : null;
    }

    function isEncaixe() {
        return isDono && !!encaixeCheck?.checked;
    }

    function guestDadosOk() {
        if (!isGuest) return true;
        const nome = document.getElementById('guestNome')?.value.trim();
        const tel = (document.getElementById('guestTelefone')?.value || '').replace(/\D/g, '');
        return !!(nome && tel.length >= 10);
    }

    function formatDuracao(d) {
        const h = Math.floor(d / 60);
        const m = d % 60;
        return h > 0 ? `${h}h ${m}min` : `${m}min`;
    }

    function atualizarResumo(duracao = getDuracao()) {
        if (!resumoCard) return;
        if (!(duracao > 0)) {
            resumoCard.classList.add('d-none');
            return;
        }
        const v = getValor();
        resumoCard.classList.remove('d-none');
        const elDur = document.getElementById('resumoDuracao') || document.getElementById('duracaoTotal');
        const elVal = document.getElementById('resumoValor') || document.getElementById('valorTotal');
        if (elDur) elDur.textContent = formatDuracao(duracao);
        if (elVal) elVal.textContent = 'R$ ' + v.toFixed(2).replace('.', ',');
    }

    function verificarBotao() {
        if (!btnSubmit) return;
        if (isReagendar) {
            btnSubmit.disabled = !picker?.getValue();
            return;
        }
        const temServico = getDuracao() > 0 && getManicureId();
        if (isEncaixe()) {
            btnSubmit.disabled = !(temServico && encaixeDatetime?.value);
            return;
        }
        btnSubmit.disabled = !(temServico && picker?.inputData?.value && picker?.getValue() && guestDadosOk());
    }

    function syncEncaixeUi() {
        if (!isDono) return;
        const on = isEncaixe();
        encaixeBox?.classList.toggle('d-none', !on);
        slotsPickerRoot?.classList.toggle('d-none', on);
        if (on && encaixeDatetime?.value && dataHoraHidden) {
            dataHoraHidden.value = encaixeDatetime.value.replace('T', ' ') + ':00';
        }
        verificarBotao();
    }

    const picker = createSlotPicker({
        getManicureId,
        getDuracao,
        manicureId: isReagendar ? opts.manicureId : undefined,
        duracao: isReagendar ? opts.duracao : undefined,
        hold: opts.hold === true || isGuest,
        emptyHint: opts.emptyHint
            || (isReagendar
                ? 'Escolha uma data para ver os horários disponíveis.'
                : 'Selecione manicure, serviços e data para ver os horários.'),
        onChange: () => verificarBotao(),
        onSlotsLoaded: (slots, ctx) => {
            if (resumoCard && !isReagendar) {
                if (slots?.length) atualizarResumo(ctx.duracao);
                else if (isGuest) resumoCard.classList.add('d-none');
                else atualizarResumo();
            }
            verificarBotao();
        },
    });

    document.querySelectorAll('.servico-check').forEach((c) => {
        c.addEventListener('change', function () {
            this.closest('label')?.querySelector('.servico-option')?.classList.toggle('selected', this.checked);
            const sel = document.querySelector(`.variacao-select[data-servico-id="${this.dataset.servicoId}"]`);
            if (sel) {
                sel.disabled = !this.checked;
                if (this.checked) syncVariacao(sel);
            }
            atualizarResumo();
            if (!isEncaixe()) picker?.load();
            verificarBotao();
        });
    });

    document.querySelectorAll('.variacao-select').forEach((sel) => {
        sel.addEventListener('change', () => {
            syncVariacao(sel);
            atualizarResumo();
            if (!isEncaixe()) picker?.load();
            verificarBotao();
        });
    });

    document.querySelectorAll('.manicure-radio').forEach((r) => {
        r.addEventListener('change', function () {
            document.querySelectorAll('.manicure-card').forEach((card) => card.classList.remove('selected'));
            this.closest('label')?.querySelector('.manicure-card')?.classList.add('selected');
            if (!isEncaixe()) picker?.load();
            verificarBotao();
        });
    });

    selectManicure?.addEventListener('change', () => {
        if (!isEncaixe()) picker?.load();
        verificarBotao();
    });

    encaixeCheck?.addEventListener('change', syncEncaixeUi);
    encaixeDatetime?.addEventListener('change', syncEncaixeUi);
    encaixeDatetime?.addEventListener('input', syncEncaixeUi);

    ['guestNome', 'guestTelefone'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', verificarBotao);
    });

    const oldDt = dataHoraHidden?.value;
    if (oldDt && picker?.inputData && !isEncaixe()) {
        const dt = oldDt.replace(' ', 'T');
        if (dt.length >= 10) {
            picker.inputData.value = dt.slice(0, 10);
            Promise.resolve(picker.load()).then(() => picker.selectDatetime(oldDt));
        }
    }

    if (isDono) syncEncaixeUi();
    if (getDuracao() > 0) atualizarResumo();
    verificarBotao();

    return { picker, getDuracao, getManicureId, verificarBotao };
}

window.initBookingForm = initBookingForm;
