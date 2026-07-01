@extends('layouts.app')

@section('title', 'Minhas folgas')
@section('page-title', 'Minhas folgas')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-plus text-pink me-2"></i>Solicitar folga</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('manicure.folgas.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data *</label>
                        <input type="date" name="data" required min="{{ today()->toDateString() }}"
                               class="form-control @error('data') is-invalid @enderror" value="{{ old('data') }}">
                        @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo</label>
                        <input type="text" name="motivo" maxlength="255"
                               class="form-control" value="{{ old('motivo') }}"
                               placeholder="Ex: Consulta médica, viagem...">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="dia_todo" value="0">
                        <input class="form-check-input" type="checkbox" name="dia_todo" value="1" id="diaTodo" checked>
                        <label class="form-check-label fw-semibold" for="diaTodo">Dia inteiro</label>
                    </div>
                    <div id="horariosBlock" class="row g-2 d-none">
                        <div class="col-6"><label class="form-label small">Início</label>
                            <input type="time" name="hora_inicio" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Fim</label>
                            <input type="time" name="hora_fim" class="form-control"></div>
                    </div>
                    <button type="submit" class="btn btn-pink w-100 mt-3">
                        <i class="fas fa-plus me-1"></i>Adicionar folga
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Minhas próximas folgas</h5>
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
                                @if($f->dia_todo)<i class="fas fa-clock me-1"></i>Dia inteiro
                                @else<i class="fas fa-clock me-1"></i>{{ $f->hora_inicio }} – {{ $f->hora_fim }}@endif
                            </small>
                        </div>
                        <form action="{{ route('manicure.folgas.destroy', $f) }}" method="POST"
                              data-confirm="Remover folga?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost text-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-umbrella-beach"></i></div>
                        <h6 class="fw-bold">Sem folgas cadastradas</h6>
                        <p>Adicione folgas para ficar fora da agenda nesses dias.</p>
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
</script>
@endpush
@endsection
