/* ===========================
   Fernanda Silva Nails — JS Principal
   =========================== */

// Vendor bundlados via Vite
import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';

// Slot picker compartilhado (agendar / reagendar) — expõe window.createSlotPicker
import './booking-slots.js';

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

        const setExpanded = function (aberto) {
            sidebarToggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', aberto ? 'Fechar menu lateral' : 'Abrir menu lateral');
        };

        const openSidebar = function () {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
            document.body.classList.add('sidebar-open');
            setExpanded(true);
        };
        const closeSidebar = function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
            setExpanded(false);
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

    // ---- Modais: reforça aria-modal (Bootstrap já faz focus trap / restore) ----
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        if (!modalEl.hasAttribute('aria-modal')) {
            modalEl.setAttribute('aria-modal', 'true');
        }
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
            navigator.serviceWorker.register('/sw.js').then(function (reg) {
                subscribeWebPushIfConfigured(reg);
            }).catch(function (err) {
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

/**
 * Web Push — só tenta se meta vapid-public-key estiver presente (VAPID no .env).
 * Sem chaves / sem PushManager / sem permissão: no-op silencioso.
 */
function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

function subscribeWebPushIfConfigured(registration) {
    var vapidMeta = document.querySelector('meta[name="vapid-public-key"]');
    var urlMeta = document.querySelector('meta[name="push-subscribe-url"]');
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!vapidMeta?.content || !urlMeta?.content || !csrf) return;
    if (!('PushManager' in window) || !registration?.pushManager) return;

    var ensureAndSave = function () {
        return registration.pushManager.getSubscription().then(function (existing) {
            if (existing) return existing;
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidMeta.content),
            });
        }).then(function (subscription) {
            if (!subscription) return;
            return fetch(urlMeta.content, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(subscription.toJSON()),
            });
        }).catch(function () { /* permissão negada / SW sem push — silencioso */ });
    };

    if (typeof Notification === 'undefined') return;

    if (Notification.permission === 'granted') {
        ensureAndSave();
    } else if (Notification.permission === 'default') {
        // Pedido sem gesto do usuário pode falhar em alguns browsers — ok para fundação.
        Notification.requestPermission().then(function (permission) {
            if (permission === 'granted') ensureAndSave();
        }).catch(function () {});
    }
}

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

// ---- Premium FX: scroll-reveal, contadores, tilt 3D, navbar scrolled ----
// Tudo progressivo (sem JS = conteúdo visível) e respeitando reduced-motion.
document.addEventListener('DOMContentLoaded', function () {
    var reduzMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Navbar pública / topbar ganham sombra ao rolar
    var chrome = document.querySelectorAll('.public-navbar, .topbar');
    if (chrome.length) {
        var aoRolar = function () {
            var rolou = window.scrollY > 10;
            chrome.forEach(function (el) { el.classList.toggle('is-scrolled', rolou); });
        };
        window.addEventListener('scroll', aoRolar, { passive: true });
        aoRolar();
    }

    // Contador animado nos valores de estatística (ex.: "R$ 1.234,56" → sobe até o valor)
    function animarContador(el) {
        if (el.dataset.contado) return;
        el.dataset.contado = '1';
        if (reduzMovimento) return;

        var m = el.textContent.trim().match(/^([^\d-]*)([\d.,]+)(.*)$/);
        if (!m) return;
        var valor = parseFloat(m[2].replace(/\./g, '').replace(',', '.'));
        if (isNaN(valor) || valor === 0) return;
        var decimais = (m[2].split(',')[1] || '').length;

        var inicio = null, dur = 900;
        function quadro(ts) {
            if (!inicio) inicio = ts;
            var p = Math.min((ts - inicio) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
            el.textContent = m[1] + (valor * eased).toLocaleString('pt-BR', {
                minimumFractionDigits: decimais, maximumFractionDigits: decimais,
            }) + m[3];
            if (p < 1) requestAnimationFrame(quadro);
        }
        requestAnimationFrame(quadro);
    }

    // Scroll-reveal com stagger
    if (!reduzMovimento && 'IntersectionObserver' in window) {
        var alvos = document.querySelectorAll('.card, .stat-card, .agenda-item, .galeria-thumb, .empty-state');
        var vistos = 0;
        var io = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) return;
                var el = entrada.target;
                el.style.setProperty('--reveal-delay', ((vistos++ % 6) * 55) + 'ms');
                el.classList.add('reveal-in');
                io.unobserve(el);
                el.querySelectorAll('.stat-value').forEach(animarContador);
            });
        }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });

        alvos.forEach(function (el) { el.classList.add('reveal'); io.observe(el); });
    } else {
        // Sem observer/reduced-motion: só dispara os contadores marcados como "vistos"
        document.querySelectorAll('.stat-value').forEach(animarContador);
    }

    // Tilt 3D sutil nos stat cards (só mouse; vars lidas pelo CSS no :hover)
    if (!reduzMovimento && window.matchMedia('(pointer: fine)').matches) {
        document.addEventListener('mousemove', function (e) {
            var card = e.target.closest && e.target.closest('.stat-card');
            if (!card) return;
            var r = card.getBoundingClientRect();
            var ry = ((e.clientX - r.left) / r.width - 0.5) * 6;
            var rx = (0.5 - (e.clientY - r.top) / r.height) * 6;
            card.style.setProperty('--rx', rx.toFixed(2) + 'deg');
            card.style.setProperty('--ry', ry.toFixed(2) + 'deg');
        }, { passive: true });

        document.addEventListener('mouseout', function (e) {
            var card = e.target.closest && e.target.closest('.stat-card');
            if (card && !card.contains(e.relatedTarget)) {
                card.style.setProperty('--rx', '0deg');
                card.style.setProperty('--ry', '0deg');
            }
        }, true);
    }
});

// ---- Helper: format BRL currency ----
function formatBRL(value) {
    return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
