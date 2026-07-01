/* ===========================
   ManicurePro — JS Principal
   =========================== */

document.addEventListener('DOMContentLoaded', function () {
    // ---- Sidebar Toggle ----
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 992 &&
                sidebar && sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ---- Auto-dismiss alerts ----
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ---- Format currency inputs ----
    document.querySelectorAll('input[data-currency]').forEach(function (input) {
        input.addEventListener('blur', function () {
            const val = parseFloat(this.value.replace(',', '.'));
            if (!isNaN(val)) {
                this.value = val.toFixed(2).replace('.', ',');
            }
        });
    });

    // ---- CEP Mask ----
    document.querySelectorAll('input[name="cep"]').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .slice(0, 9);
        });
    });

    // ---- Phone Mask ----
    document.querySelectorAll('input[name="telefone"], input[name="whatsapp"], input[name="phone"]').forEach(function (input) {
        input.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 6) {
                v = v.replace(/(\d{2})(\d{5,})(\d{4})$/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/(\d{2})(\d+)/, '($1) $2');
            }
            this.value = v;
        });
    });

    // ---- Loading states automáticos em forms ----
    // Desabilita botão de submit e mostra spinner durante o envio
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noLoading === 'true') return;
        // Aguarda o final do tick para checar se houve preventDefault (ex: Confirm modal)
        setTimeout(function () {
            if (form.dataset.confirm && !form.dataset.confirmDone) return; // ainda esperando confirm
            const btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn || btn.disabled) return;

            const originalHtml = btn.innerHTML;
            btn.dataset.originalHtml = originalHtml;
            btn.disabled = true;
            btn.classList.add('btn-loading');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processando...';

            // Failsafe: se nada acontecer em 15s, reabilita o botão
            setTimeout(function () {
                if (btn.disabled && btn.dataset.originalHtml) {
                    btn.disabled = false;
                    btn.classList.remove('btn-loading');
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            }, 15000);
        }, 10);
    });

    // ---- Tooltips Bootstrap ----
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ---- PWA Install Prompt ----
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;

        const installBtn = document.getElementById('installBtn');
        if (installBtn) {
            installBtn.style.display = 'block';
            installBtn.addEventListener('click', function () {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choice) {
                    deferredPrompt = null;
                    installBtn.style.display = 'none';
                });
            });
        }
    });

    // ---- Service Worker ----
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.warn('SW registration failed:', err);
        });
    }
});

// ---- Helper: format BRL currency ----
function formatBRL(value) {
    return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
