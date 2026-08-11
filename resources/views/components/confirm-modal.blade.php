{{-- Modal de confirmação global (substitui confirm() nativo) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true"
     aria-labelledby="confirmTitle" aria-describedby="confirmMessage" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 confirm-modal-content">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mx-auto mb-3 d-flex align-items-center justify-content-center"
                     id="confirmIcon"
                     aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                </div>
                <h5 class="fw-bold mb-2" id="confirmTitle">Tem certeza?</h5>
                <p class="text-muted mb-0" id="confirmMessage">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" id="confirmCancel">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger px-4" id="confirmOk">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.Confirm = {
    /**
     * Confirma uma ação. Retorna Promise<boolean>.
     *
     * Uso:
     *   const ok = await Confirm.show({title:'Excluir?', message:'...', type:'danger'});
     *   if (ok) form.submit();
     */
    show({title = 'Tem certeza?', message = 'Esta ação não pode ser desfeita.', type = 'danger', okLabel = 'Confirmar', cancelLabel = 'Cancelar'} = {}) {
        return new Promise(resolve => {
            const modal = document.getElementById('confirmModal');
            const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            const icon = document.getElementById('confirmIcon');
            const titleEl = document.getElementById('confirmTitle');
            const msgEl = document.getElementById('confirmMessage');
            const okBtn = document.getElementById('confirmOk');
            const cancelBtn = document.getElementById('confirmCancel');

            const styles = {
                danger:  { bg: 'var(--danger-soft)',  color: 'var(--danger)',  icon: 'fa-triangle-exclamation', btn: 'btn-danger'  },
                warning: { bg: 'var(--warning-soft)', color: 'var(--warning)', icon: 'fa-circle-exclamation',   btn: 'btn-warning' },
                info:    { bg: 'var(--info-soft)',    color: 'var(--info)',    icon: 'fa-circle-info',          btn: 'btn-primary' },
                success: { bg: 'var(--success-soft)', color: 'var(--success)', icon: 'fa-circle-check',         btn: 'btn-success' },
                pink:    { bg: 'var(--pink-100)',     color: 'var(--pink-600)',icon: 'fa-hand-sparkles',        btn: 'btn-pink'    },
            };
            const s = styles[type] || styles.danger;

            icon.style.background = s.bg;
            icon.style.color = s.color;
            icon.innerHTML = `<i class="fa-solid ${s.icon}" aria-hidden="true"></i>`;

            titleEl.textContent = title;
            msgEl.textContent = message;
            okBtn.textContent = okLabel;
            cancelBtn.textContent = cancelLabel;

            okBtn.className = `btn ${s.btn} px-4`;

            let resolved = false;
            const finish = (v) => {
                if (resolved) return;
                resolved = true;
                bsModal.hide();
                okBtn.replaceWith(okBtn.cloneNode(true));
                resolve(v);
            };

            document.getElementById('confirmOk').addEventListener('click', () => finish(true), {once:true});
            modal.addEventListener('hidden.bs.modal', () => finish(false), {once:true});
            modal.addEventListener('shown.bs.modal', () => {
                // Foco no cancelar (ação menos destrutiva) após o modal abrir
                document.getElementById('confirmCancel')?.focus();
            }, {once:true});

            bsModal.show();
        });
    }
};

/**
 * Intercepta forms com data-confirm — não precisa adicionar onsubmit
 *
 * Uso: <form data-confirm="Excluir?" data-confirm-message="...">
 */
document.addEventListener('submit', async function(e) {
    const form = e.target;
    if (!form.dataset.confirm || form.dataset.confirmDone) return;
    e.preventDefault();
    const ok = await Confirm.show({
        title: form.dataset.confirm,
        message: form.dataset.confirmMessage || 'Esta ação não pode ser desfeita.',
        type: form.dataset.confirmType || 'danger',
        okLabel: form.dataset.confirmOk || 'Confirmar',
    });
    if (ok) {
        form.dataset.confirmDone = '1';
        form.submit();
    }
});
</script>
