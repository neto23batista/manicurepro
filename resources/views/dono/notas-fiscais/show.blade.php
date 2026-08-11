@extends('layouts.app')

@section('title', 'NF stub #' . $nota->id)
@section('page-title', 'NF stub #' . $nota->id)

@section('content')
<div class="alert alert-warning alert-permanent mb-4">
    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
    <div>
        <strong>Stub — NÃO emitir SEFAZ.</strong>
        Este registro não foi autorizado nem transmitido a nenhum órgão fiscal.
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Rascunho local</h5>
                <span class="badge bg-{{ $nota->status_color }}">{{ $nota->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Número</label>
                        <p class="fw-semibold mb-0">{{ $nota->numero ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Chave de acesso</label>
                        <p class="fw-semibold mb-0 font-monospace small">{{ $nota->chave ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Agendamento</label>
                        <p class="mb-0">
                            @if($nota->agendamento)
                                <a href="{{ route('dono.agendamentos.show', $nota->agendamento) }}">
                                    #{{ $nota->agendamento->id }}
                                </a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Comanda</label>
                        <p class="mb-0">{{ $nota->comanda_id ? '#'.$nota->comanda_id : '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Payload (stub)</label>
                        <pre class="bg-light border rounded p-3 small mb-0">{{ json_encode($nota->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body d-grid gap-2">
                <a href="{{ route('dono.notas-fiscais.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Lista de rascunhos
                </a>
                @if($nota->agendamento)
                    <a href="{{ route('dono.agendamentos.show', $nota->agendamento) }}" class="btn btn-outline-pink">
                        <i class="fas fa-calendar-check me-2"></i>Ver agendamento
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
