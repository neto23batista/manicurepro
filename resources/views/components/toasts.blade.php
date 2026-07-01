{{-- Sistema global de toasts (notificações flash) --}}
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<style>
.toast-stack {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 380px;
    pointer-events: none;
}
.toast-item {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid;
    pointer-events: auto;
    animation: toastIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
    overflow: hidden;
}
.toast-item.toast-success { border-color: var(--success); }
.toast-item.toast-error   { border-color: var(--danger); }
.toast-item.toast-warning { border-color: var(--warning); }
.toast-item.toast-info    { border-color: var(--info); }
.toast-item.toast-pink    { border-color: var(--pink-500); }

.toast-item .toast-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}
.toast-success .toast-icon { background: var(--success-soft); color: var(--success); }
.toast-error   .toast-icon { background: var(--danger-soft);  color: var(--danger); }
.toast-warning .toast-icon { background: var(--warning-soft); color: var(--warning); }
.toast-info    .toast-icon { background: var(--info-soft);    color: var(--info); }
.toast-pink    .toast-icon { background: var(--pink-100);     color: var(--pink-600); }

.toast-content { flex: 1; min-width: 0; }
.toast-title { font-weight: 600; font-size: 14px; color: var(--ink-900); line-height: 1.3; }
.toast-message { font-size: 12.5px; color: var(--ink-600); margin-top: 2px; line-height: 1.4; }

.toast-close {
    background: transparent; border: none;
    color: var(--ink-400);
    cursor: pointer;
    padding: 4px;
    transition: color var(--t-fast);
    line-height: 1;
}
.toast-close:hover { color: var(--ink-700); }

.toast-progress {
    position: absolute;
    bottom: 0; left: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.35;
    animation: toastProgress linear forwards;
}
.toast-success .toast-progress { color: var(--success); }
.toast-error   .toast-progress { color: var(--danger); }
.toast-warning .toast-progress { color: var(--warning); }
.toast-info    .toast-progress { color: var(--info); }
.toast-pink    .toast-progress { color: var(--pink-500); }

@keyframes toastIn {
    from { opacity: 0; transform: translateX(100%); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes toastOut {
    to { opacity: 0; transform: translateX(100%); }
}
@keyframes toastProgress {
    from { width: 100%; }
    to   { width: 0%; }
}
.toast-item.toast-leaving { animation: toastOut 0.3s ease forwards; }
</style>

<script>
window.Toast = {
    show(type, title, message = null, duration = 5000) {
        const stack = document.getElementById('toastStack');
        if (!stack) return;

        const icons = {
            success: 'fa-circle-check',
            error:   'fa-circle-xmark',
            warning: 'fa-triangle-exclamation',
            info:    'fa-circle-info',
            pink:    'fa-hand-sparkles',
        };

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon"><i class="fa-solid ${icons[type] || icons.info}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${this._escape(title)}</div>
                ${message ? `<div class="toast-message">${this._escape(message)}</div>` : ''}
            </div>
            <button type="button" class="toast-close" aria-label="Fechar">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
        `;

        stack.appendChild(toast);

        const remove = () => {
            toast.classList.add('toast-leaving');
            setTimeout(() => toast.remove(), 300);
        };

        toast.querySelector('.toast-close').addEventListener('click', remove);
        setTimeout(remove, duration);

        return toast;
    },
    success(title, message = null) { return this.show('success', title, message); },
    error(title, message = null)   { return this.show('error', title, message, 7000); },
    warning(title, message = null) { return this.show('warning', title, message); },
    info(title, message = null)    { return this.show('info', title, message); },
    pink(title, message = null)    { return this.show('pink', title, message); },
    _escape(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
};

// Auto-disparar toasts vindos do flash session
@if(session('success'))   Toast.success(@json(session('success'))); @endif
@if(session('error'))     Toast.error(@json(session('error'))); @endif
@if(session('warning'))   Toast.warning(@json(session('warning'))); @endif
@if(session('info'))      Toast.info(@json(session('info'))); @endif
@if($errors->any())
    @foreach($errors->all() as $error)
        Toast.error(@json($error));
    @endforeach
@endif
</script>
