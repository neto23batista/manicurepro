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

<div class="command-palette" id="commandPalette" role="dialog" aria-label="Busca rápida">
    <div class="command-box">
        <div class="command-search">
            <i class="fas fa-search text-pink"></i>
            <input type="text" id="commandInput" placeholder="O que você procura? Digite para filtrar..." autocomplete="off">
            <kbd>ESC</kbd>
        </div>
        <div class="command-results" id="commandResults"></div>
        <div class="command-footer">
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
    let activeIndex = 0;
    let filtered = [];

    function open() {
        palette.classList.add('open');
        input.value = '';
        render();
        setTimeout(() => input.focus(), 50);
    }
    function close() {
        palette.classList.remove('open');
    }
    function normalize(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function render() {
        const q = normalize(input.value.trim());
        filtered = q
            ? window.commandList.filter(c => normalize(c.label).includes(q) || normalize(c.group).includes(q))
            : window.commandList;
        activeIndex = 0;

        if (!filtered.length) {
            results.innerHTML = '<div class="command-empty"><i class="fas fa-search-minus fa-2x mb-2 d-block opacity-25"></i>Nada encontrado para "' + escapeHtml(input.value) + '"</div>';
            return;
        }

        // Agrupa
        const groups = {};
        filtered.forEach(c => { (groups[c.group] = groups[c.group] || []).push(c); });

        let html = '';
        let i = 0;
        for (const [groupName, items] of Object.entries(groups)) {
            html += `<div class="command-group-label">${escapeHtml(groupName)}</div>`;
            items.forEach(c => {
                html += `<a href="${c.url}" class="command-item${i === activeIndex ? ' is-active' : ''}" data-index="${i}">
                    <i class="fas ${c.icon}"></i>
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
            el.classList.toggle('is-active', idx === activeIndex);
            if (idx === activeIndex) el.scrollIntoView({block: 'nearest'});
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
            close();
        }
    });

    // Expor globalmente
    window.CommandPalette = { open, close };
})();
</script>
@endauth
