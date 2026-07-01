@extends('layouts.app')

@section('title', 'Vale ' . $vale->codigo)
@section('page-title', 'Vale-presente')

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 font-monospace">{{ $vale->codigo }}</h5>
                <span class="badge bg-{{ $vale->status_color }} fs-6">{{ $vale->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="text-muted small">Valor original</label>
                        <p class="fw-semibold fs-5 mb-0">@money($vale->valor)</p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Saldo atual</label>
                        <p class="fw-bold fs-5 text-pink mb-0">@money($vale->saldo)</p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Comprador</label>
                        <p class="mb-0">{{ $vale->comprador_nome ?: '—' }}
                            @if($vale->comprador_contato)<br><small class="text-muted">{{ $vale->comprador_contato }}</small>@endif
                        </p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Beneficiário</label>
                        <p class="mb-0">{{ $vale->beneficiario_nome ?: '—' }}</p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Emitido em</label>
                        <p class="mb-0">{{ $vale->created_at->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Validade</label>
                        <p class="mb-0">{{ $vale->validade ? $vale->validade->format('d/m/Y') : 'Sem validade' }}</p>
                    </div>
                    @if($vale->mensagem)
                        <div class="col-12">
                            <label class="text-muted small">Mensagem</label>
                            <p class="mb-0 fst-italic">"{{ $vale->mensagem }}"</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Como usar</h5></div>
            <div class="card-body">
                <p class="text-muted small">
                    Informe o código <strong class="font-monospace">{{ $vale->codigo }}</strong> na tela do atendimento,
                    no botão <em>Aplicar vale-presente</em>. O saldo será usado para abater o total da comanda.
                </p>
                <div class="d-grid gap-2">
                    <a href="{{ route('dono.vales.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                    @if($vale->status === \App\Models\ValePresente::STATUS_ATIVO)
                        <form method="POST" action="{{ route('dono.vales.cancelar', $vale) }}"
                              data-confirm="Cancelar vale?" data-confirm-message="O saldo deixará de ser utilizável." data-confirm-ok="Cancelar vale">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger w-100"><i class="fas fa-ban me-1"></i>Cancelar vale</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
