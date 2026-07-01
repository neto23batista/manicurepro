@props(['status', 'label' => null, 'color' => null])

@php
    use App\Enums\AgendamentoStatus;

    // Aceita string ou AgendamentoStatus
    $enum = $status instanceof AgendamentoStatus ? $status : AgendamentoStatus::tryFrom((string) $status);

    $resolvedLabel = $label ?? ($enum?->label() ?? (string) $status);
    $resolvedColor = $color ?? ($enum?->color() ?? 'secondary');
@endphp

<span {{ $attributes->merge(['class' => "badge bg-{$resolvedColor}"]) }}>
    {{ $resolvedLabel }}
</span>
