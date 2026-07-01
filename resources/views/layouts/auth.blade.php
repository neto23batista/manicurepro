<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acesso') - {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#e91e8c">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <meta name="app-env" content="{{ app()->environment() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="auth-logo-icon">F</div>
                <div class="auth-logo-text">{{ config('app.name') }}</div>
                <div class="auth-logo-sub">@yield('subtitle', 'Sistema de Gestão')</div>
            </div>

            @yield('content')
        </div>

        <div class="auth-footer">
            <p class="mb-0">
                <i class="fas fa-hand-sparkles text-pink"></i>
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Toasts globais --}}
    @include('components.toasts')
</body>
</html>
