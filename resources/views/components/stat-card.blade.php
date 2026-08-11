@props([
    'icon'     => 'fa-chart-line',
    'label'    => '',
    'value'    => '',
    'color'    => 'pink',
    'href'     => null,
    'subtitle' => null,
    'delta'    => null,
])

@php
    $tag = $href ? 'a' : 'div';
    $deltaNum = is_numeric($delta) ? (float) $delta : null;
@endphp

<{!! $tag !!}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'stat-card']) }}
>
    <div class="stat-icon bg-{{ $color }}-light">
        <i class="fas {{ $icon }} text-{{ $color }}" aria-hidden="true"></i>
    </div>
    <div class="stat-info">
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
        @if($subtitle)
            <small class="text-muted">{{ $subtitle }}</small>
        @endif
        @if($deltaNum !== null)
            <small class="d-block mt-1 {{ $deltaNum >= 0 ? 'text-success' : 'text-danger' }}">
                <i class="fas fa-{{ $deltaNum >= 0 ? 'arrow-up' : 'arrow-down' }}" aria-hidden="true"></i>
                {{ number_format(abs($deltaNum), 1, ',', '.') }}% vs mês anterior
            </small>
        @endif
    </div>
</{!! $tag !!}>
