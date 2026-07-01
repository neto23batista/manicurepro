@extends('layouts.app')

@section('title', 'Vale-presente')
@section('page-title', 'Vale-presente')

@section('content')
{{-- Resumo --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Total emitido</div>
                <div class="fs-4 fw-bold">@money($resumo['emitido'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Saldo em circulação</div>
                <div class="fs-4 fw-bold text-pink">@money($resumo['saldo'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Vales disponíveis</div>
                <div class="fs-4 fw-bold">{{ $resumo['ativos'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-gift text-pink me-2"></i>Vales emitidos</h5>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="busca" value="{{ request('busca') }}"
                       class="form-control form-control-sm" placeholder="Código ou nome…" style="min-width:160px">
                <select name="status" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todos</option>
                    <option value="ativo" @selected(request('status')==='ativo')>Ativos</option>
                    <option value="usado" @selected(request('status')==='usado')>Usados</option>
                    <option value="cancelado" @selected(request('status')==='cancelado')>Cancelados</option>
                </select>
            </form>
            <button type="button" class="btn btn-pink btn-sm" data-bs-toggle="modal" data-bs-target="#novoVale">
                <i class="fas fa-plus me-1"></i>Emitir vale
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        @forelse($vales as $vale)
            <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="flex-grow-1">
                    <a href="{{ route('dono.vales.show', $vale) }}" class="fw-semibold text-decoration-none font-monospace">{{ $vale->codigo }}</a>
                    <span class="badge bg-{{ $vale->status_color }} ms-2">{{ $vale->status_label }}</span>
                    <div class="small text-muted">
                        @if($vale->beneficiario_nome)Para {{ $vale->beneficiario_nome }} · @endif
                        @if($vale->comprador_nome)de {{ $vale->comprador_nome }} · @endif
                        emitido {{ $vale->created_at->format('d/m/Y') }}
                        @if($vale->validade) · vence {{ $vale->validade->format('d/m/Y') }}@endif
                    </div>
                </div>
                <div class="text-center px-3">
                    <div class="text-muted small">Saldo</div>
                    <div class="fw-bold">@money($vale->saldo)</div>
                </div>
                <div class="text-center px-3 d-none d-md-block">
                    <div class="text-muted small">Valor</div>
                    <div class="fw-semibold">@money($vale->valor)</div>
                </div>
                <a href="{{ route('dono.vales.show', $vale) }}" class="btn btn-sm btn-ghost" title="Ver"><i class="fas fa-eye"></i></a>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-gift"></i></div>
                <h6 class="fw-bold">Nenhum vale-presente emitido</h6>
                <p>Venda vales-presente e fidelize quem presenteia.</p>
                <button type="button" class="btn btn-pink btn-sm" data-bs-toggle="modal" data-bs-target="#novoVale">
                    <i class="fas fa-plus me-1"></i>Emitir vale
                </button>
            </div>
        @endforelse
    </div>

    @if($vales->hasPages())
        <div class="card-footer">{{ $vales->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Modal: emitir vale --}}
<div class="modal fade" id="novoVale" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Emitir vale-presente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="{{ route('dono.vales.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Valor (R$)</label>
                            <input type="number" name="valor" step="0.01" min="1" class="form-control @error('valor') is-invalid @enderror" value="{{ old('valor') }}" required>
                            @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Forma de pagamento</label>
                            <select name="forma" class="form-select @error('forma') is-invalid @enderror">
                                @foreach(\App\Models\Pagamento::FORMAS_LABELS as $val => $label)
                                    @continue($val === 'voucher')
                                    <option value="{{ $val }}" @selected(old('forma', 'dinheiro') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('forma')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Comprador <small class="text-muted">(opcional)</small></label>
                            <input type="text" name="comprador_nome" class="form-control" value="{{ old('comprador_nome') }}" maxlength="255">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Contato do comprador</label>
                            <input type="text" name="comprador_contato" class="form-control" value="{{ old('comprador_contato') }}" maxlength="255">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Para quem é <small class="text-muted">(opcional)</small></label>
                        <input type="text" name="beneficiario_nome" class="form-control" value="{{ old('beneficiario_nome') }}" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mensagem <small class="text-muted">(opcional)</small></label>
                        <textarea name="mensagem" rows="2" maxlength="500" class="form-control">{{ old('mensagem') }}</textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Validade <small class="text-muted">(opcional)</small></label>
                        <input type="date" name="validade" class="form-control @error('validade') is-invalid @enderror" value="{{ old('validade') }}" min="{{ now()->toDateString() }}">
                        @error('validade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-pink"><i class="fas fa-gift me-1"></i>Emitir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
