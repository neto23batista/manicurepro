@extends('layouts.app')

@section('title', 'Notas fiscais (stub)')
@section('page-title', 'Notas fiscais (stub)')

@section('content')
<div class="alert alert-warning alert-permanent mb-4">
    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
    <div>
        <strong>Módulo stub — NÃO emitir SEFAZ.</strong>
        Estes registros são rascunhos locais apenas. Não há autorização, transmissão
        nem integração com webservice fiscal.
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-file-invoice text-pink me-2"></i>Rascunhos locais</h5>
        <a href="{{ route('dono.financeiro.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-cash-register me-1"></i>Voltar ao caixa
        </a>
    </div>
    <div class="card-body p-0">
        @forelse($notas as $nota)
            <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="flex-grow-1">
                    <a href="{{ route('dono.notas-fiscais.show', $nota) }}" class="fw-semibold text-decoration-none">
                        NF stub #{{ $nota->id }}
                    </a>
                    <span class="badge bg-{{ $nota->status_color }} ms-2">{{ $nota->status_label }}</span>
                    <div class="small text-muted">
                        @if($nota->agendamento_id)
                            Agendamento #{{ $nota->agendamento_id }}
                        @endif
                        @if($nota->comanda_id)
                            · Comanda #{{ $nota->comanda_id }}
                        @endif
                        · {{ $nota->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                <div class="text-end px-3">
                    <div class="text-muted small">Total (payload)</div>
                    <div class="fw-bold">@money(data_get($nota->payload, 'total', 0))</div>
                </div>
                <a href="{{ route('dono.notas-fiscais.show', $nota) }}" class="btn btn-sm btn-ghost" title="Ver">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        @empty
            <div class="empty-state py-4">
                <div class="empty-state-icon"><i class="fas fa-file-invoice"></i></div>
                <p class="mb-0">Nenhum rascunho fiscal ainda.</p>
                <p class="small text-muted mb-0">Gere a partir de um agendamento concluído.</p>
            </div>
        @endforelse
    </div>
    @if(method_exists($notas, 'links') && $notas->hasPages())
        <div class="card-footer">{{ $notas->links() }}</div>
    @endif
</div>
@endsection
