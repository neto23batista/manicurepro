@extends('layouts.app')

@section('title', 'Minhas folgas')
@section('page-title', 'Minhas folgas')

@section('content')
<div class="alert alert-info alert-permanent d-flex align-items-start gap-2 mb-4">
    <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
    <div>
        <strong>Como funcionam as folgas</strong>
        <div class="small mb-0">
            Ao cadastrar uma folga, sua agenda fica bloqueada nesse período e clientes não conseguem agendar com você.
            Remova a folga se quiser voltar a aceitar horários nesse dia.
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-plus text-pink me-2"></i>Solicitar folga</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('manicure.folgas.store') }}" method="POST" id="formFolga">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="folgaData">Data *</label>
                        <input type="date" name="data" id="folgaData" required min="{{ today()->toDateString() }}"
                               class="form-control @error('data') is-invalid @enderror" value="{{ old('data') }}">
                        @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">A data precisa ser hoje ou futura.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="folgaMotivo">Motivo</label>
                        <input type="text" name="motivo" id="folgaMotivo" maxlength="255"
                               class="form-control" value="{{ old('motivo') }}"
                               placeholder="Ex: Consulta médica, viagem...">
                        <div class="form-text">Opcional — ajuda você a lembrar o motivo depois.</div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="dia_todo" value="0">
                            <input class="form-check-input" type="checkbox" name="dia_todo" value="1" id="diaTodo"
                                   @checked(old('dia_todo', '1') == '1')>
                            <label class="form-check-label fw-semibold" for="diaTodo">Dia inteiro</label>
                        </div>
                        <p class="form-text mb-0 mt-2" id="diaTodoHelp">
                            Bloqueia todos os horários do dia na sua agenda.
                        </p>
                    </div>

                    <div id="horariosBlock" class="row g-2 {{ old('dia_todo', '1') == '1' ? 'd-none' : '' }}">
                        <div class="col-12">
                            <p class="small text-muted mb-2">
                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                Informe o intervalo em que você estará indisponível.
                            </p>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="horaInicio">Início *</label>
                            <input type="time" name="hora_inicio" id="horaInicio" class="form-control @error('hora_inicio') is-invalid @enderror"
                                   value="{{ old('hora_inicio') }}">
                            @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="horaFim">Fim *</label>
                            <input type="time" name="hora_fim" id="horaFim" class="form-control @error('hora_fim') is-invalid @enderror"
                                   value="{{ old('hora_fim') }}">
                            @error('hora_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pink w-100 mt-3" id="btnSalvarFolga">
                        <i class="fas fa-plus me-1"></i>Adicionar folga
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Minhas folgas</h5>
                <span class="badge bg-pink-light text-pink">{{ $folgas->total() }} cadastrada{{ $folgas->total() === 1 ? '' : 's' }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($folgas as $f)
                    @php
                        $passada = $f->data->lt(today());
                        $hoje = $f->data->isToday();
                    @endphp
                    <div class="d-flex align-items-start align-items-md-center p-3 gap-2 {{ !$loop->last ? 'border-bottom' : '' }} {{ $passada ? 'opacity-75' : '' }}">
                        <div class="me-1 text-center flex-shrink-0" style="min-width:60px">
                            <div class="fw-bold text-pink fs-5 lh-1">{{ $f->data->format('d') }}</div>
                            <small class="text-muted text-uppercase">{{ $f->data->translatedFormat('M') }}</small>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <div class="fw-semibold">{{ $f->motivo ?: 'Folga' }}</div>
                                @if($hoje)
                                    <span class="badge bg-pink">Hoje</span>
                                @elseif($passada)
                                    <span class="badge bg-secondary">Passada</span>
                                @else
                                    <span class="badge bg-info text-dark">Próxima</span>
                                @endif
                                @if($f->dia_todo)
                                    <span class="badge bg-yellow-light text-dark">Dia inteiro</span>
                                @else
                                    <span class="badge bg-light text-dark border">Parcial</span>
                                @endif
                            </div>
                            <small class="text-muted d-block">
                                <i class="fas fa-calendar-day me-1" aria-hidden="true"></i>
                                {{ $f->data->translatedFormat('l') }} · {{ $f->data->format('d/m/Y') }}
                            </small>
                            <small class="text-muted d-block">
                                @if($f->dia_todo)
                                    <i class="fas fa-ban me-1" aria-hidden="true"></i>Agenda bloqueada o dia todo
                                @else
                                    <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                    Bloqueio das {{ \Illuminate\Support\Str::of($f->hora_inicio)->substr(0, 5) }}
                                    às {{ \Illuminate\Support\Str::of($f->hora_fim)->substr(0, 5) }}
                                @endif
                            </small>
                        </div>
                        <form action="{{ route('manicure.folgas.destroy', $f) }}" method="POST" class="flex-shrink-0"
                              data-confirm="Remover folga?"
                              data-confirm-message="Em {{ $f->data->format('d/m/Y') }} sua agenda voltará a aceitar agendamentos{{ $f->dia_todo ? '' : ' neste horário' }}."
                              data-confirm-type="warning"
                              data-confirm-ok="Remover">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost text-danger" style="min-height:40px;min-width:40px"
                                    title="Remover folga" aria-label="Remover folga de {{ $f->data->format('d/m/Y') }}">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-umbrella-beach"></i></div>
                        <h6 class="fw-bold">Sem folgas cadastradas</h6>
                        <p>Adicione uma folga para bloquear horários e evitar novos agendamentos nesses dias.</p>
                    </div>
                @endforelse
            </div>
            @if($folgas->hasPages())
                <div class="card-footer">{{ $folgas->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const diaTodo = document.getElementById('diaTodo');
    const horarios = document.getElementById('horariosBlock');
    const help = document.getElementById('diaTodoHelp');
    const horaInicio = document.getElementById('horaInicio');
    const horaFim = document.getElementById('horaFim');

    function syncFolgaTipo() {
        const inteiro = diaTodo.checked;
        horarios.classList.toggle('d-none', inteiro);
        help.textContent = inteiro
            ? 'Bloqueia todos os horários do dia na sua agenda.'
            : 'Desmarque para bloquear só um intervalo (ex.: manhã ou tarde).';
        horaInicio.required = !inteiro;
        horaFim.required = !inteiro;
        if (inteiro) {
            horaInicio.value = '';
            horaFim.value = '';
        }
    }

    diaTodo.addEventListener('change', syncFolgaTipo);
    syncFolgaTipo();
})();
</script>
@endpush
