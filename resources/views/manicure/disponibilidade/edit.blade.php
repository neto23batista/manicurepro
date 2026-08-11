@extends('layouts.app')

@section('title', 'Minha disponibilidade')
@section('page-title', 'Minha disponibilidade')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-clock text-pink me-2"></i>Expediente e pausa</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">A pausa (ex.: almoço) bloqueia slots para clientes automaticamente.</p>
        <form method="POST" action="{{ route('manicure.disponibilidade.update') }}">
            @csrf
            @method('PUT')
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Ativo</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Pausa início</th>
                            <th>Pausa fim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dias as $num => $nome)
                            @php $d = $disp->get($num); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $nome }}</td>
                                <td>
                                    <input type="hidden" name="dias[{{ $num }}][ativo]" value="0">
                                    <input type="checkbox" class="form-check-input" name="dias[{{ $num }}][ativo]" value="1"
                                           @checked(old("dias.$num.ativo", $d?->ativo))>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="dias[{{ $num }}][hora_inicio]"
                                           value="{{ old("dias.$num.hora_inicio", $d ? substr((string) $d->hora_inicio, 0, 5) : '09:00') }}">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="dias[{{ $num }}][hora_fim]"
                                           value="{{ old("dias.$num.hora_fim", $d ? substr((string) $d->hora_fim, 0, 5) : '18:00') }}">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="dias[{{ $num }}][pausa_inicio]"
                                           value="{{ old("dias.$num.pausa_inicio", $d && $d->pausa_inicio ? substr((string) $d->pausa_inicio, 0, 5) : '') }}">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="dias[{{ $num }}][pausa_fim]"
                                           value="{{ old("dias.$num.pausa_fim", $d && $d->pausa_fim ? substr((string) $d->pausa_fim, 0, 5) : '') }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-pink">
                <i class="fas fa-save me-1"></i> Salvar
            </button>
        </form>
    </div>
</div>
@endsection
