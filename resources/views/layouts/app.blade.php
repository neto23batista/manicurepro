<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Sistema de gestão para salões de manicure')">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ec4899">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link rel="apple-touch-icon" href="/images/icon-192.png">

    {{-- Tema (claro/escuro) aplicado antes da pintura para evitar flash --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    {{-- Vite bundle (Bootstrap, Font Awesome, ApexCharts e app.css/js) --}}
    <meta name="app-env" content="{{ app()->environment() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <x-sidebar />

    <main class="main-content" id="mainContent">
        <x-topbar :title="View::yieldContent('page-title', 'Dashboard')" />

        <div class="page-content">
            @yield('content')
        </div>
    </main>

    {{-- Componentes globais --}}
    @include('components.toasts')
    @include('components.confirm-modal')
    @include('components.command-palette')
    @include('components.fab')

    @stack('scripts')
</body>
</html>
