@props(['user', 'menus'])

@php
    if (!function_exists('manicure_is_active')) {
        function manicure_is_active($pattern) {
            if (!$pattern) return false;
            return is_array($pattern)
                ? collect($pattern)->contains(fn($p) => request()->routeIs($p))
                : request()->routeIs($pattern);
        }
    }
@endphp

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">F</div>
        <div class="brand-text">
            <div class="brand-name">{{ config('app.name') }}</div>
            <div class="brand-sub">{{ $user?->salao?->nome ?? 'Sistema' }}</div>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Menu principal">
        @foreach($menus as $grupo)
            <div class="nav-label">{{ $grupo['label'] }}</div>
            @foreach($grupo['items'] as $item)
                <a href="{{ route($item['route']) }}"
                   class="nav-item {{ manicure_is_active($item['active_pattern']) ? 'active' : '' }}"
                   aria-label="{{ $item['label'] }}">
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach

        <div class="nav-divider"></div>
        <div class="nav-label">Conta</div>
        <a href="{{ route('perfil.edit') }}"
           class="nav-item {{ request()->routeIs('perfil*') ? 'active' : '' }}"
           aria-label="Meu Perfil">
            <i class="fas fa-user-circle" aria-hidden="true"></i>
            <span>Meu Perfil</span>
        </a>
        <a href="{{ route('2fa.setup') }}"
           class="nav-item {{ request()->routeIs('2fa.setup') ? 'active' : '' }}"
           aria-label="Verificação em duas etapas">
            <i class="fas fa-shield-halved" aria-hidden="true"></i>
            <span>Segurança (2FA)</span>
        </a>
        <a href="/" class="nav-item" aria-label="Ir para o site público">
            <i class="fas fa-globe" aria-hidden="true"></i>
            <span>Site Público</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <img src="{{ $user?->avatar_url }}" alt="Avatar de {{ $user?->name }}" class="user-avatar">
            <div class="user-details">
                <div class="user-name">{{ $user?->name }}</div>
                <div class="user-role">{{ ucfirst($user?->role ?? '') }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Sair
            </button>
        </form>
    </div>
</div>
