{{-- Command Palette estilo Spotlight/Linear (Ctrl+K) --}}
@auth
@php
    $role = auth()->user()->role;

    // Comandos contextuais por role
    $commands = [];
    if ($role === 'admin') {
        $commands = [
            ['group' => 'Navegação',  'icon' => 'fa-chart-pie',      'label' => 'Dashboard',          'url' => route('admin.dashboard')],
            ['group' => 'Navegação',  'icon' => 'fa-store',          'label' => 'Meu Salão',          'url' => route('admin.saloes.index')],
            ['group' => 'Navegação',  'icon' => 'fa-hand-sparkles',  'label' => 'Manicures',          'url' => route('admin.manicures.index')],
            ['group' => 'Navegação',  'icon' => 'fa-spa',            'label' => 'Serviços',           'url' => route('admin.servicos.index')],
            ['group' => 'Navegação',  'icon' => 'fa-tags',           'label' => 'Categorias',         'url' => route('admin.categorias.index')],
            ['group' => 'Navegação',  'icon' => 'fa-users',          'label' => 'Usuários',           'url' => route('admin.usuarios.index')],
            ['group' => 'Navegação',  'icon' => 'fa-file-lines',     'label' => 'Relatórios',         'url' => route('admin.relatorios.index')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Nova manicure',      'url' => route('admin.manicures.create')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo serviço',       'url' => route('admin.servicos.create')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo usuário',       'url' => route('admin.usuarios.create')],
            ['group' => 'Conta',      'icon' => 'fa-user-circle',    'label' => 'Meu perfil',         'url' => route('perfil.edit')],
        ];
    } elseif (in_array($role, ['dono', 'atendente'])) {
        $commands = [
            ['group' => 'Navegação',  'icon' => 'fa-chart-pie',      'label' => 'Dashboard',          'url' => route('dono.dashboard')],
            ['group' => 'Navegação',  'icon' => 'fa-calendar-check', 'label' => 'Agendamentos',       'url' => route('dono.agendamentos.index')],
            ['group' => 'Navegação',  'icon' => 'fa-users',          'label' => 'Clientes',           'url' => route('dono.clientes.index')],
            ['group' => 'Navegação',  'icon' => 'fa-ticket',         'label' => 'Cupons',             'url' => route('dono.cupons.index')],
            ['group' => 'Navegação',  'icon' => 'fa-umbrella-beach', 'label' => 'Folgas',             'url' => route('dono.folgas.index')],
            ['group' => 'Navegação',  'icon' => 'fa-gear',           'label' => 'Configurações',      'url' => route('dono.config.edit')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo agendamento',   'url' => route('dono.agendamentos.create')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo cliente',       'url' => route('dono.clientes.create')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo cupom',         'url' => route('dono.cupons.create')],
            ['group' => 'Conta',      'icon' => 'fa-user-circle',    'label' => 'Meu perfil',         'url' => route('perfil.edit')],
        ];
    } elseif ($role === 'manicure') {
        $commands = [
            ['group' => 'Navegação',  'icon' => 'fa-chart-pie',      'label' => 'Dashboard',          'url' => route('manicure.dashboard')],
            ['group' => 'Navegação',  'icon' => 'fa-calendar-alt',   'label' => 'Minha agenda',       'url' => route('manicure.agenda.index')],
            ['group' => 'Navegação',  'icon' => 'fa-umbrella-beach', 'label' => 'Minhas folgas',      'url' => route('manicure.folgas.index')],
            ['group' => 'Conta',      'icon' => 'fa-user-circle',    'label' => 'Meu perfil',         'url' => route('perfil.edit')],
        ];
    } else { // cliente
        $commands = [
            ['group' => 'Navegação',  'icon' => 'fa-home',           'label' => 'Início',             'url' => route('cliente.dashboard')],
            ['group' => 'Navegação',  'icon' => 'fa-calendar-check', 'label' => 'Meus agendamentos',  'url' => route('cliente.agendamentos.index')],
            ['group' => 'Ações',      'icon' => 'fa-plus',           'label' => 'Novo agendamento',   'url' => route('cliente.agendamentos.create')],
            ['group' => 'Conta',      'icon' => 'fa-user-circle',    'label' => 'Meu perfil',         'url' => route('perfil.edit')],
        ];
    }
@endphp

<div class="command-palette" id="commandPalette" role="dialog" aria-modal="true" aria-label="Busca rápida" aria-hidden="true" hidden>
    <div class="command-box" role="document">
        <div class="command-search">
            <i class="fas fa-search text-pink" aria-hidden="true"></i>
            <input type="text" id="commandInput" placeholder="O que você procura? Digite para filtrar..." autocomplete="off" aria-label="Buscar comandos" aria-controls="commandResults" role="combobox" aria-expanded="true" aria-autocomplete="list">
            <kbd aria-hidden="true">ESC</kbd>
        </div>
        <div class="command-results" id="commandResults" role="listbox" aria-label="Resultados"></div>
        <div class="command-footer" aria-hidden="true">
            <span><kbd>↑↓</kbd> navegar &nbsp; <kbd>↵</kbd> selecionar</span>
            <span>Pressione <kbd>Ctrl</kbd>+<kbd>K</kbd> a qualquer momento</span>
        </div>
    </div>
</div>

<script>
window.commandList = @json($commands);

(function() {
    const palette = document.getElementById('commandPalette');
    const input   = document.getElementById('commandInput');
    const results = document.getElementById('commandResults');
    const box     = palette.querySelector('.command-box');
    let activeIndex = 0;
    let filtered = [];
    let previousFocus = null;
    const reduzMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function focusables() {
        return [...box.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
    }

    function open() {
        if (palette.classList.contains('open')) {
            input.focus();
            return;
        }
        previousFocus = document.activeElement;
        palette.hidden = false;
        palette.setAttribute('aria-hidden', 'false');
        palette.classList.add('open');
        input.value = '';
        render();
        setTimeout(() => input.focus(), 50);
    }
    function close() {
        if (!palette.classList.contains('open')) return;
        palette.classList.remove('open');
        palette.setAttribute('aria-hidden', 'true');
        palette.hidden = true;
        const restore = previousFocus;
        previousFocus = null;
        if (restore && typeof restore.focus === 'function') {
            restore.focus();
        }
    }
    function normalize(s) {
        return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    function render() {
        const q = normalize(input.value.trim());
        filtered = q
            ? window.commandList.filter(c => normalize(c.label).includes(q) || normalize(c.group).includes(q))
            : window.commandList;
        activeIndex = 0;

        if (!filtered.length) {
            results.innerHTML = '<div class="command-empty" role="status"><i class="fas fa-search-minus fa-2x mb-2 d-block opacity-25" aria-hidden="true"></i>Nada encontrado para "' + escapeHtml(input.value) + '"</div>';
            return;
        }

        // Agrupa
        const groups = {};
        filtered.forEach(c => { (groups[c.group] = groups[c.group] || []).push(c); });

        let html = '';
        let i = 0;
        for (const [groupName, items] of Object.entries(groups)) {
            html += `<div class="command-group-label" role="presentation">${escapeHtml(groupName)}</div>`;
            items.forEach(c => {
                html += `<a href="${c.url}" class="command-item${i === activeIndex ? ' is-active' : ''}" data-index="${i}" role="option" aria-selected="${i === activeIndex ? 'true' : 'false'}">
                    <i class="fas ${c.icon}" aria-hidden="true"></i>
                    <span>${escapeHtml(c.label)}</span>
                </a>`;
                i++;
            });
        }
        results.innerHTML = html;
    }
    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function updateActive() {
        results.querySelectorAll('.command-item').forEach((el, idx) => {
            const ativo = idx === activeIndex;
            el.classList.toggle('is-active', ativo);
            el.setAttribute('aria-selected', ativo ? 'true' : 'false');
            if (ativo) el.scrollIntoView({ block: 'nearest', behavior: reduzMovimento ? 'auto' : 'smooth' });
        });
    }

    // Eventos
    input.addEventListener('input', render);

    input.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
            updateActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActive();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const item = results.querySelectorAll('.command-item')[activeIndex];
            if (item) window.location = item.href;
        } else if (e.key === 'Escape') {
            close();
        } else if (e.key === 'Tab') {
            const els = focusables();
            if (!els.length) {
                e.preventDefault();
                return;
            }
            const first = els[0];
            const last = els[els.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    palette.addEventListener('keydown', function(e) {
        if (!palette.classList.contains('open')) return;
        if (e.key !== 'Tab') return;
        // Trap Tab when focus is outside the input (e.g. on result links)
        if (e.target === input) return;
        const els = focusables();
        if (!els.length) return;
        const first = els[0];
        const last = els[els.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    palette.addEventListener('click', function(e) {
        if (e.target === palette) close();
    });

    // Atalhos globais
    document.addEventListener('keydown', function(e) {
        // Ctrl+K ou Cmd+K
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            open();
            return;
        }
        // / abre busca (se não estiver dentro de input)
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            open();
            return;
        }
        // ESC fecha
        if (e.key === 'Escape' && palette.classList.contains('open')) {
            e.preventDefault();
            close();
        }
    });

    // Expor globalmente
    window.CommandPalette = { open, close };
})();
</script>
@endauth
