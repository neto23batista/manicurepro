@extends('layouts.app')

@section('title', 'Remarcar agendamento')
@section('page-title', 'Remarcar agendamento')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @include('agendamentos._reagendar', [
            'action'  => route('dono.agendamentos.reagendar', $agendamento),
            'backUrl' => route('dono.agendamentos.show', $agendamento),
        ])
    </div>
</div>
@endsection
