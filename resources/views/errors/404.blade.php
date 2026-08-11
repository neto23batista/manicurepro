<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página Não Encontrada | {{ config('app.name') }}</title>
    <x-theme-vars />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: linear-gradient(135deg, #fff5fb 0%, #fce4ec 100%); min-height: 100vh; display: flex; align-items: center; }
        .error-code { font-size: 6rem; font-weight: 900; color: var(--pink); line-height: 1; }
        .icon-big { font-size: 5rem; color: var(--pink); opacity: .25; }
    </style>
</head>
<body>
    <div class="container text-center py-5">
        <div class="mb-3">
            <i class="fa-solid fa-magnifying-glass icon-big"></i>
        </div>
        <div class="error-code mb-2">404</div>
        <h2 class="fw-bold mb-2">Página Não Encontrada</h2>
        <p class="text-muted mb-4 fs-5">
            A página que você procura não existe ou foi movida.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}"
               class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>Voltar
            </a>
            @auth
                @php
                    $dashRoute = match(auth()->user()->role) {
                        'admin' => route('admin.dashboard'),
                        'dono', 'atendente' => route('dono.dashboard'),
                        'manicure' => route('manicure.dashboard'),
                        default => route('cliente.dashboard'),
                    };
                @endphp
                <a href="{{ $dashRoute }}" class="btn btn-pink px-4">
                    <i class="fa-solid fa-house me-1"></i>Ir para o início
                </a>
            @else
            <a href="/" class="btn btn-pink px-4">
                <i class="fa-solid fa-house me-1"></i>Página inicial
            </a>
            @endauth
        </div>
    </div>
</body>
</html>
