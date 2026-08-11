@props(['cliente', 'showCount' => true])

@if($cliente && $cliente->eh_risco_no_show)
    @php
        $faltas = (int) $cliente->total_faltas;
        $label = $showCount
            ? $faltas.' '.\Illuminate\Support\Str::plural('falta', $faltas)
            : 'Risco no-show';
    @endphp
    <span {{ $attributes->merge(['class' => 'badge bg-warning text-dark']) }}
          title="Cliente com {{ $faltas }} {{ \Illuminate\Support\Str::plural('falta', $faltas) }} (não comparecimento)">
        <i class="fas fa-user-xmark me-1" aria-hidden="true"></i>{{ $label }}
    </span>
@endif
