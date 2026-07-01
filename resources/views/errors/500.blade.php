<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erro Interno | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --pink: #e91e8c; }
        body { background: linear-gradient(135deg, #fff5fb 0%, #fce4ec 100%); min-height: 100vh; display: flex; align-items: center; }
        .error-code { font-size: 6rem; font-weight: 900; color: var(--pink); line-height: 1; }
        .icon-big { font-size: 5rem; color: var(--pink); opacity: .25; }
        .btn-pink { background: var(--pink); color: white; border: none; }
        .btn-pink:hover { background: #c2177c; color: white; }
    </style>
</head>
<body>
    <div class="container text-center py-5">
        <div class="mb-3">
            <i class="fa-solid fa-triangle-exclamation icon-big"></i>
        </div>
        <div class="error-code mb-2">500</div>
        <h2 class="fw-bold mb-2">Erro Interno do Servidor</h2>
        <p class="text-muted mb-4 fs-5">
            Algo deu errado no servidor. Nossa equipe foi notificada e está trabalhando para resolver.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="javascript:location.reload()" class="btn btn-outline-secondary">
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
