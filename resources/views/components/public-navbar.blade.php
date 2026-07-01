@props([
    'backUrl' => null,
    'backLabel' => 'Voltar',
    'showAuthButtons' => true,
])

<nav class="navbar navbar-expand-lg sticky-top public-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
            <div class="brand-logo-sm brand-logo-gradient">F</div>
            <span class="text-gradient public-brand-text">{{ config('app.name') }}</span>
        </a>

        {{ $slot ?? '' }}

        <div class="ms-auto d-flex gap-2 align-items-center">
            @if($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-outline-pink btn-sm">
                    <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> {{ $backLabel }}
                </a>
            @elseif($showAuthButtons)
                @auth
                    <a href="{{ route('cliente.dashboard') }}" class="btn btn-pink btn-sm">
                        <i class="fas fa-user me-1" aria-hidden="true"></i> Meu painel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-pink btn-sm">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-pink btn-sm">Cadastrar</a>
                @endauth
            @endif
        </div>
    </div>
</nav>
