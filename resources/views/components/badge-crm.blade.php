@props(['segmentos' => []])

@php
    $crm = app(\App\Services\ClienteSegmentacao::class);
@endphp

@foreach($segmentos as $seg)
    <span {{ $attributes->class(['badge', 'bg-'.$crm->cor($seg)]) }}
          title="Segmento CRM: {{ $crm->label($seg) }}">
        {{ $crm->label($seg) }}
    </span>
@endforeach
