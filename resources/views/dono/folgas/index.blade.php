@extends('layouts.app')

@section('title', 'Folgas e Feriados')
@section('page-title', 'Folgas e Feriados do salão')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-plus text-pink me-2"></i>Cadastrar folga</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dono.folgas.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data *</label>
                        <input type="date" name="data" required min="{{ today()->toDateString() }}"
                               class="form-control @error('data') is-invalid @enderror"
                               value="{{ old('data') }}">
                        @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo</label>
                        <input type="text" name="motivo" maxlength="255"
                               class="form-control"
                               value="{{ old('motivo') }}"
                               placeholder="Ex: Reforma, evento interno...">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="dia_todo" value="0">
                        <input class="form-check-input" type="checkbox" name="dia_todo" value="1" id="diaTodo" checked>
                        <label class="form-check-label fw-semibold" for="diaTodo">Dia inteiro</label>
                    </div>
                    <div id="horariosBlock" class="row g-2 d-none">
                        <div class="col-6">
                            <label class="form-label small">Início</label>
                            <input type="time" name="hora_inicio" class="form-control" value="{{ old('hora_inicio') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Fim</label>
                            <input type="time" name="hora_fim" class="form-control" value="{{ old('hora_fim') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-pink w-100 mt-3">
                        <i class="fas fa-plus me-1"></i> Adicionar folga
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-star text-pink me-2"></i>Feriado recorrente (anual)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Repete todo ano na mesma data (ex.: Natal 25/12).</p>
                <form action="{{ route('dono.feriados.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome *</label>
                        <input type="text" name="nome" required maxlength="255"
                               class="form-control @error('nome') is-invalid @enderror"
                               value="{{ old('nome') }}"
                               placeholder="Ex: Natal">
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Dia *</label>
                            <input type="number" name="dia" min="1" max="31" required
                                   class="form-control @error('dia') is-invalid @enderror"
                                   value="{{ old('dia') }}">
                            @error('dia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Mês *</label>
                            <select name="mes" class="form-select @error('mes') is-invalid @enderror" required>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" @selected(old('mes') == $m)>
                                        {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('mes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="dia_todo" value="0">
                        <input class="form-check-input" type="checkbox" name="dia_todo" value="1" id="feriadoDiaTodo" checked>
                        <label class="form-check-label fw-semibold" for="feriadoDiaTodo">Dia inteiro</label>
                    </div>
                    <div id="feriadoHorarios" class="row g-2 d-none">
                        <div class="col-6">
                            <label class="form-label small">Início</label>
                            <input type="time" name="hora_inicio" class="form-control" value="{{ old('hora_inicio') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Fim</label>
                            <input type="time" name="hora_fim" class="form-control" value="{{ old('hora_fim') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-pink w-100 mt-3">
                        <i class="fas fa-plus me-1"></i> Adicionar feriado
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Próximas folgas</h5>
            </div>
            <div class="card-body p-0">
                @forelse($folgas as $f)
                    <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 text-center" style="min-width:60px">
                            <div class="fw-bold text-pink fs-5">{{ $f->data->format('d') }}</div>
                            <small class="text-muted text-uppercase">{{ $f->data->format('M') }}</small>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $f->motivo ?: 'Folga' }}</div>
                            <small class="text-muted">
                                @if($f->dia_todo)
                                    <i class="fas fa-clock me-1"></i>Dia inteiro
                                @else
                                    <i class="fas fa-clock me-1"></i>{{ $f->hora_inicio }} – {{ $f->hora_fim }}
                                @endif
                                · {{ $f->data->translatedFormat('l') }}
                            </small>
                        </div>
                        <form action="{{ route('dono.folgas.destroy', $f) }}" method="POST"
                              data-confirm="Remover folga?" data-confirm-message="A data {{ $f->data->format('d/m/Y') }} voltará a aceitar agendamentos.">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-umbrella-beach"></i></div>
                        <h6 class="fw-bold">Nenhuma folga programada</h6>
                        <p>Cadastre folgas pontuais para bloquear agendamentos.</p>
                    </div>
                @endforelse
            </div>
            @if($folgas->hasPages())
                <div class="card-footer">{{ $folgas->links() }}</div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-star text-pink me-2"></i>Feriados anuais</h5>
            </div>
            <div class="card-body p-0">
                @forelse($feriados as $feriado)
                    <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 text-center" style="min-width:60px">
                            <div class="fw-bold text-pink fs-5">{{ sprintf('%02d', $feriado->dia) }}</div>
                            <small class="text-muted text-uppercase">{{ \Carbon\Carbon::create(null, $feriado->mes, 1)->format('M') }}</small>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $feriado->nome }}</div>
                            <small class="text-muted">
                                @if($feriado->dia_todo)
                                    Dia inteiro · todo ano
                                @else
                                    {{ $feriado->hora_inicio }} – {{ $feriado->hora_fim }} · todo ano
                                @endif
                            </small>
                        </div>
                        <form action="{{ route('dono.feriados.destroy', $feriado) }}" method="POST"
                              data-confirm="Remover feriado?" data-confirm-message="{{ $feriado->nome }} deixará de bloquear a agenda.">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state py-4">
                        <h6 class="fw-bold">Nenhum feriado cadastrado</h6>
                        <p class="mb-0 text-muted small">Cadastre feriados nacionais/regionais que se repetem todo ano.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('diaTodo').addEventListener('change', function() {
    document.getElementById('horariosBlock').classList.toggle('d-none', this.checked);
});
document.getElementById('feriadoDiaTodo').addEventListener('change', function() {
    document.getElementById('feriadoHorarios').classList.toggle('d-none', this.checked);
});
</script>
@endpush
@endsection
