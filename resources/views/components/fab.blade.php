{{-- Floating Action Button contextual por role --}}
@auth
@php
    $role = auth()->user()->role;
    $fab = null;

    if (in_array($role, ['dono', 'atendente'])) {
        $fab = [
            'href'  => route('dono.agendamentos.create'),
            'icon'  => 'fa-calendar-plus',
            'label' => 'Novo agendamento',
        ];
    } elseif ($role === 'cliente') {
        $fab = [
            'href'  => route('cliente.agendamentos.create'),
            'icon'  => 'fa-calendar-plus',
            'label' => 'Agendar',
        ];
    } elseif ($role === 'admin') {
        $fab = [
            'href'  => route('admin.servicos.create'),
            'icon'  => 'fa-plus',
            'label' => 'Novo serviço',
        ];
    } elseif ($role === 'manicure') {
        $fab = [
            'href'  => route('manicure.agenda.index'),
            'icon'  => 'fa-calendar-day',
            'label' => 'Ver agenda',
        ];
    }
@endphp

@if($fab)
<a href="{{ $fab['href'] }}"
   class="fab"
   data-bs-toggle="tooltip"
   data-bs-placement="left"
   title="{{ $fab['label'] }}"
   aria-label="{{ $fab['label'] }}"
   id="fabButton">
    <i class="fas {{ $fab['icon'] }}" aria-hidden="true"></i>
</a>

<style>
/* Esconde o FAB em telas pequenas (não interferir com mobile nav) */
@media (max-width: 575px) {
    .fab { bottom: 16px; right: 16px; width: 52px; height: 52px; font-size: 18px; }
}
/* Esconde quando o command palette está aberto */
.command-palette.open ~ .fab { display: none !important; }
</style>
@endif
@endauth
