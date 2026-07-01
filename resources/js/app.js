/* ===========================
   Fernanda Silva Nails — JS Principal
   =========================== */

// Vendor bundlados via Vite
import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';

// Expor globalmente para uso em scripts inline nas views
window.bootstrap = bootstrap;
window.ApexCharts = ApexCharts;

document.addEventListener('DOMContentLoaded', function () {
    // ---- Sidebar Toggle (off-canvas no mobile) ----
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebar && sidebarToggle) {
        // Backdrop tocável criado dinamicamente (não exige alteração no layout)
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);

        const openSidebar = function () {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
            document.body.classList.add('sidebar-open');
        };
        const closeSidebar = function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        };

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        // Toque no backdrop fecha
        backdrop.addEventListener('click', closeSidebar);

        // Navegar por um item fecha o menu no mobile
        sidebar.querySelectorAll('.nav-item').forEach(function (item) {
            item.addEventListener('click', function () {
                if (window.innerWidth <= 991) closeSidebar();
            });
        });

        // Tecla ESC fecha
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
        });

        // Ao voltar para desktop, garante estado limpo
        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) closeSidebar();
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
    // Fonte da verdade: meta app-env gerada pelo servidor (APP_ENV) — o gate
    // fica no ambiente, não no bundle. Só registra o SW em produção; fora dela
    // remove SW/caches antigos deste host (evita que o cache de outro projeto
    // servido na mesma porta "sequestre" a página). Fallback sem a meta:
    // heurística de hostname local.
    if ('serviceWorker' in navigator) {
        var appEnv = document.querySelector('meta[name="app-env"]')?.content
            || (['localhost', '127.0.0.1', '[::1]'].indexOf(location.hostname) !== -1 ? 'local' : 'production');

        if (appEnv === 'production') {
            navigator.serviceWorker.register('/sw.js').catch(function (err) {
                console.warn('SW registration failed:', err);
            });
        } else {
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                regs.forEach(function (reg) { reg.unregister(); });
            }).catch(function () {});
            if (window.caches && caches.keys) {
                caches.keys().then(function (chaves) {
                    chaves.forEach(function (c) { caches.delete(c); });
                }).catch(function () {});
            }
        }
    }
});

// ---- Alternar tema claro/escuro ----
window.toggleTheme = function () {
    const proximo = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', proximo);
    try { localStorage.setItem('theme', proximo); } catch (e) {}
};

// ---- Ações declarativas (substituem handlers inline; CSP-safe) ----
// click: [data-action] e [data-submit-form]
document.addEventListener('click', function (e) {
    const acao = e.target.closest('[data-action]');
    if (acao) {
        switch (acao.dataset.action) {
            case 'toggle-theme':
                window.toggleTheme();
                break;
            case 'command-palette':
                window.CommandPalette?.open?.();
                break;
        }
    }

    // Botão que dispara o submit de um form por id (ex.: remover logo/capa)
    const disparo = e.target.closest('[data-submit-form]');
    if (disparo) {
        e.preventDefault();
        document.getElementById(disparo.dataset.submitForm)?.submit();
    }
});

// click: lightbox da galeria — abre a imagem ampliada num modal compartilhado (CSP-safe)
document.addEventListener('click', function (e) {
    const item = e.target.closest('[data-galeria-src]');
    if (!item) return;

    const modalEl = document.getElementById('galeriaLightbox');
    if (!modalEl || !window.bootstrap) return;

    const img = modalEl.querySelector('[data-galeria-target="img"]');
    const cap = modalEl.querySelector('[data-galeria-target="caption"]');
    if (img) {
        img.src = item.dataset.galeriaSrc;
        img.alt = item.dataset.galeriaTitulo || 'Trabalho';
    }
    if (cap) cap.textContent = item.dataset.galeriaTitulo || '';

    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
});

// change: select[data-autosubmit] envia o form ao mudar
document.addEventListener('change', function (e) {
    const sel = e.target.closest('select[data-autosubmit]');
    if (sel && sel.form) sel.form.submit();
});

// submit: newsletter do rodapé (apenas feedback visual, sem backend)
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form instanceof HTMLFormElement && form.hasAttribute('data-newsletter')) {
        e.preventDefault();
        const btn = form.querySelector('button');
        if (btn) btn.textContent = 'Obrigada!';
    }
});

// ---- Helper: format BRL currency ----
function formatBRL(value) {
    return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
