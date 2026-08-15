{{-- Lista de erros de validação anunciável para leitores de tela --}}
@props(['bag' => null])

@php
    $errorsBag = $bag instanceof \Illuminate\Support\ViewErrorBag
        ? $bag
        : ($errors ?? null);
@endphp

@if ($errorsBag && $errorsBag->any())
    <div {{ $attributes->merge(['class' => 'alert alert-danger']) }}
         role="alert"
         aria-live="assertive"
         aria-atomic="true">
        <p class="fw-semibold mb-2">Corrija os erros abaixo:</p>
        <ul class="mb-0 ps-3">
            @foreach ($errorsBag->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
