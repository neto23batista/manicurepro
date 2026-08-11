@extends('layouts.app')

@section('title', 'Disponibilidade')
@section('page-title', 'Disponibilidade das manicures')

@section('content')
<p class="text-muted mb-4">Defina expediente e pausa/almoço por dia. Slots públicos respeitam a pausa automaticamente.</p>

@forelse($manicures as $manicure)
    @php $disp = $manicure->disponibilidades->keyBy('dia_semana'); @endphp
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-user text-pink me-2"></i>{{ $manicure->nome }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('dono.disponibilidades.update', $manicure) }}">
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
                    <i class="fas fa-save me-1"></i> Salvar {{ $manicure->nome }}
                </button>
            </form>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">Nenhuma manicure cadastrada.</div>
    </div>
@endforelse
@endsection
