<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acesso') - {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <x-theme-vars />
    <meta name="app-env" content="{{ app()->environment() }}">
    {{-- Bootstrap + Font Awesome vêm do bundle Vite (sem CDN — alinhado à CSP). --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <x-skip-link />
    <div class="auth-container">
        <div class="auth-card" id="mainContent" tabindex="-1">
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

    {{-- Toasts globais --}}
    @include('components.toasts')
</body>
</html>
