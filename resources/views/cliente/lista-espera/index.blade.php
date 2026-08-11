@extends('layouts.app')

@section('title', 'Lista de espera')
@section('page-title', 'Lista de espera')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" id="entrar-lista">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2 text-pink"></i>Entrar na lista de espera</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Sem horário na agenda? Entre na lista e avisamos quando abrir uma vaga que combine com você.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger alert-permanent">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('cliente.lista-espera.store') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="salao_id" value="{{ \App\Models\Salao::principalId() }}">
                    <div class="col-12">
                        <label class="form-label" for="data_preferida">Data preferida <small class="text-muted">(opcional)</small></label>
                        <input type="date" name="data_preferida" id="data_preferida"
                               class="form-control form-control-lg"
                               min="{{ today()->toDateString() }}"
                               value="{{ old('data_preferida') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="periodo">Período preferido</label>
                        <select name="periodo" id="periodo" class="form-select form-select-lg">
                            <option value="qualquer" @selected(old('periodo', 'qualquer') === 'qualquer')>Qualquer horário</option>
                            <option value="manha" @selected(old('periodo') === 'manha')>Manhã</option>
                            <option value="tarde" @selected(old('periodo') === 'tarde')>Tarde</option>
                            <option value="noite" @selected(old('periodo') === 'noite')>Noite</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-pink w-100 py-2">
                            <i class="fas fa-bell me-2"></i> Avisar quando abrir vaga
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-outline-pink btn-sm w-100 py-2">
                        <i class="fas fa-calendar-plus me-1"></i> Ou agendar agora
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-bold">Minhas inscrições</h6>
                @if($entradas->isNotEmpty())
                    <span class="badge text-bg-secondary">{{ $entradas->count() }}</span>
                @endif
            </div>
            <div class="card-body">
                @forelse($entradas as $e)
                    <div class="list-item p-3 rounded-3 {{ !$loop->last ? 'mb-2' : '' }} {{ $e->status === 'notificado' ? 'border border-success border-opacity-25' : '' }}">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-sm-between gap-3">
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="fw-semibold">{{ $e->salao->nome }}</span>
                                    <span class="badge bg-{{ $e->status_color }}">{{ $e->status_label }}</span>
                                </div>
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    {{ $e->data_preferida ? $e->data_preferida->format('d/m/Y') : 'Qualquer data' }}
                                    · {{ $e->periodo_label }}
                                </small>
                                @if($e->notificado_em)
                                    <small class="text-success d-block mt-1">
                                        <i class="fas fa-envelope-open-text me-1"></i>
                                        Aviso em {{ $e->notificado_em->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                                <p class="small text-muted mb-0 mt-2">{{ $e->status_hint }}</p>
                            </div>

                            <div class="d-flex flex-column flex-sm-row align-items-stretch gap-2 flex-shrink-0">
                                @if($e->status === 'notificado')
                                    <a href="{{ route('cliente.agendamentos.create') }}"
                                       class="btn btn-pink btn-sm py-2">
                                        <i class="fas fa-calendar-check me-1"></i> Agendar
                                    </a>
                                @endif
                                @if($e->esta_ativa)
                                    <form method="POST" action="{{ route('cliente.lista-espera.destroy', $e) }}"
                                          data-confirm="Sair da lista de espera?" data-confirm-type="warning" data-confirm-ok="Sair">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-secondary btn-sm w-100 py-2" type="submit" title="Sair da lista">
                                            <i class="fas fa-times me-1"></i> Sair
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
                        <h6 class="fw-bold">Você ainda não está na lista</h6>
                        <p class="mb-3">Cadastre sua preferência e receba um aviso quando surgir uma vaga.</p>
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                            <a href="#entrar-lista" class="btn btn-pink">
                                <i class="fas fa-bell me-2"></i> Entrar na lista
                            </a>
                            <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-outline-pink">
                                <i class="fas fa-calendar-plus me-2"></i> Agendar agora
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        @if($entradas->isNotEmpty())
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body py-3">
                    <div class="row g-3 small text-muted">
                        <div class="col-sm-6 col-lg-3">
                            <span class="badge bg-warning me-1">&nbsp;</span> Aguardando vaga
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <span class="badge bg-success me-1">&nbsp;</span> Vaga avisada
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <span class="badge bg-primary me-1">&nbsp;</span> Atendido
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <span class="badge bg-secondary me-1">&nbsp;</span> Cancelado
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
