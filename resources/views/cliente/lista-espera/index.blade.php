@extends('layouts.app')

@section('title', 'Lista de espera')
@section('page-title', 'Lista de espera')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2 text-pink"></i>Entrar na lista de espera</h6>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-permanent">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('cliente.lista-espera.store') }}">
                    @csrf
                    <input type="hidden" name="salao_id" value="{{ \App\Models\Salao::principalId() }}">
                    <div class="mb-3">
                        <label class="form-label" for="data_preferida">Data preferida <small class="text-muted">(opcional)</small></label>
                        <input type="date" name="data_preferida" id="data_preferida" class="form-control"
                               min="{{ today()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="periodo">Período</label>
                        <select name="periodo" id="periodo" class="form-select">
                            <option value="qualquer">Qualquer horário</option>
                            <option value="manha">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-pink w-100">
                        <i class="fas fa-bell me-2"></i> Avisar quando abrir vaga
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Minhas inscrições</h6>
            </div>
            <div class="card-body">
                @forelse($entradas as $e)
                    <div class="d-flex align-items-center justify-content-between list-item p-3 rounded-3">
                        <div>
                            <div class="fw-semibold">{{ $e->salao->nome }}</div>
                            <small class="text-muted">
                                {{ $e->data_preferida ? $e->data_preferida->format('d/m/Y') : 'Qualquer data' }}
                                · {{ $e->periodo_label }}
                            </small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $e->status === 'notificado' ? 'success' : 'secondary' }}">
                                {{ $e->status === 'notificado' ? 'Vaga avisada' : 'Aguardando' }}
                            </span>
                            <form method="POST" action="{{ route('cliente.lista-espera.destroy', $e) }}"
                                  data-confirm="Sair da lista de espera?" data-confirm-type="warning" data-confirm-ok="Sair">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" title="Sair"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
                        <p>Você ainda não está em nenhuma lista de espera.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
