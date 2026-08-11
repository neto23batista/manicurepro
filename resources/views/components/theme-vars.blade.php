{{-- Aplica cor_primaria do salão (DB) nas CSS variables; fallback config/env. --}}
@props(['salao' => null])
@php
    $cor = null;

    if ($salao instanceof \App\Models\Salao) {
        $cor = $salao->configuracao?->cor_primaria
            ?? \App\Models\ConfiguracaoSalao::where('salao_id', $salao->id)->value('cor_primaria');
    }

    if (! $cor && auth()->check() && auth()->user()?->salao_id) {
        $cor = auth()->user()->salao?->configuracao?->cor_primaria
            ?? \App\Models\ConfiguracaoSalao::where('salao_id', auth()->user()->salao_id)->value('cor_primaria');
    }

    if (! $cor) {
        $principal = \App\Models\Salao::principal();
        if ($principal) {
            $cor = $principal->configuracao?->cor_primaria
                ?? \App\Models\ConfiguracaoSalao::where('salao_id', $principal->id)->value('cor_primaria');
        }
    }

    if (! $cor) {
        $cor = (string) config('manicure.tema.cor_primaria', '#e91e8c');
    }

    $cor = (string) $cor;
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
