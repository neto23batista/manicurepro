@props([
    'icon'     => 'fa-chart-line',
    'label'    => '',
    'value'    => '',
    'color'    => 'pink',
    'href'     => null,
    'subtitle' => null,
])

@php
    $tag = $href ? 'a' : 'div';
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
    </div>
</{!! $tag !!}>
