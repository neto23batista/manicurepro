{{-- Aplica cor_primaria do tema nas CSS variables (CSP permite style inline). --}}
@php
    $cor = (string) config('manicure.tema.cor_primaria', '#e91e8c');
    if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $cor)) {
        $cor = '#e91e8c';
    }
@endphp
<style>
    :root {
        --cor-primaria: {{ $cor }};
        --pink-500: {{ $cor }};
        --pink-600: {{ $cor }};
        --pink: {{ $cor }};
    }
</style>
<meta name="theme-color" content="{{ $cor }}">
