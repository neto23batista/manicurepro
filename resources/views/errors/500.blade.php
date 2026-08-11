<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erro Interno | {{ config('app.name') }}</title>
    <x-theme-vars />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: linear-gradient(135deg, #fff5fb 0%, #fce4ec 100%); min-height: 100vh; display: flex; align-items: center; }
        .error-code { font-size: 6rem; font-weight: 900; color: var(--pink); line-height: 1; }
        .icon-big { font-size: 5rem; color: var(--pink); opacity: .25; }
    </style>
</head>
<body>
    <x-skip-link />
    <div class="container text-center py-5" id="mainContent" tabindex="-1">
        <div class="mb-3">
            <i class="fa-solid fa-triangle-exclamation icon-big"></i>
        </div>
        <div class="error-code mb-2">500</div>
        <h2 class="fw-bold mb-2">Erro Interno do Servidor</h2>
        <p class="text-muted mb-4 fs-5">
            Algo deu errado no servidor. Nossa equipe foi notificada e está trabalhando para resolver.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate-right me-1"></i>Tentar novamente
            </a>
            <a href="/" class="btn btn-pink px-4">
                <i class="fa-solid fa-house me-1"></i>Página inicial
            </a>
        </div>
        <p class="text-muted small mt-4">
            Se o problema persistir, entre em contato com o suporte.
        </p>
    </div>
</body>
</html>
