@props(['title' => 'Dashboard'])

<div class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu lateral">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <div class="topbar-title">{{ $title }}</div>

    <div class="topbar-actions">
        {{-- Busca rápida (Ctrl+K) --}}
        <button type="button"
                class="btn btn-ghost d-none d-md-flex align-items-center gap-2"
                data-action="command-palette"
                aria-label="Abrir busca rápida (Ctrl+K)"
                title="Busca rápida (Ctrl+K)">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="text-muted small">Buscar</span>
            <kbd class="ms-2">Ctrl K</kbd>
        </button>

        <button type="button" class="btn btn-ghost" data-action="toggle-theme"
                aria-label="Alternar tema claro/escuro" title="Tema claro/escuro">
            <i class="fas fa-circle-half-stroke" aria-hidden="true"></i>
        </button>

        <x-notifications-dropdown />
        <x-user-dropdown />
    </div>
</div>
