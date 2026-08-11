@extends('layouts.app')

@section('title', 'Agenda semanal')
@section('page-title', 'Agenda semanal')

@section('content')
@php
    $hoje = today()->toDateString();
@endphp

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
            <a href="{{ route('dono.agendamentos.semana', ['data' => $inicio->copy()->subWeek()->toDateString(), 'manicure_id' => $manicureId]) }}"
               class="btn btn-outline-pink" aria-label="Semana anterior">
                <i class="fas fa-chevron-left"></i>
                <span class="d-none d-sm-inline ms-1">Semana</span>
            </a>
            <div class="text-center flex-grow-1">
                <h5 class="mb-1 text-pink">
                    {{ $inicio->format('d/m') }} — {{ $fim->format('d/m/Y') }}
                </h5>
                <a href="{{ route('dono.agendamentos.semana') }}" class="btn btn-sm btn-pink">Hoje</a>
            </div>
            <a href="{{ route('dono.agendamentos.semana', ['data' => $inicio->copy()->addWeek()->toDateString(), 'manicure_id' => $manicureId]) }}"
               class="btn btn-outline-pink" aria-label="Próxima semana">
                <span class="d-none d-sm-inline me-1">Semana</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="data" value="{{ $inicio->toDateString() }}">
            <div class="col-md-4">
                <label class="form-label small">Manicure</label>
                <select name="manicure_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach($salao->manicures as $m)
                        <option value="{{ $m->id }}" @selected((string) $manicureId === (string) $m->id)>{{ $m->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 text-md-end">
                <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink">
                    <i class="fas fa-plus me-1"></i> Novo
                </a>
                <a href="{{ route('dono.agendamentos.index') }}" class="btn btn-outline-secondary">Lista</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 align-middle agenda-semana-grid">
                <thead>
                    <tr>
                        <th style="min-width:120px">Manicure</th>
                        @foreach($dias as $dia)
                            <th class="text-center {{ $dia->toDateString() === $hoje ? 'bg-pink-light' : '' }}">
                                <div class="small text-uppercase">{{ $dia->translatedFormat('D') }}</div>
                                <div class="fw-bold">{{ $dia->format('d/m') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($manicures as $manicure)
                        <tr>
                            <td class="fw-semibold">{{ $manicure->nome }}</td>
                            @foreach($dias as $dia)
                                @php
                                    $chave = $manicure->id.'|'.$dia->toDateString();
                                    $lista = $agendamentos->get($chave, collect());
                                @endphp
                                <td class="p-2 {{ $dia->toDateString() === $hoje ? 'bg-pink-light bg-opacity-25' : '' }}" style="min-width:140px; vertical-align:top">
                                    @forelse($lista as $ag)
                                        <a href="{{ route('dono.agendamentos.show', $ag) }}"
                                           class="d-block text-decoration-none border rounded p-2 mb-1 small {{ $ag->encaixe ? 'border-warning' : '' }}">
                                            <div class="fw-bold text-pink">{{ $ag->data_hora_inicio->format('H:i') }}</div>
                                            <div class="text-dark text-truncate">{{ $ag->nome_cliente_exibido }}</div>
                                            @if($ag->encaixe)
                                                <span class="badge bg-warning text-dark">Encaixe</span>
                                            @endif
                                        </a>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nenhuma manicure ativa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
