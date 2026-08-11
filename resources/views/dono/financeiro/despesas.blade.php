@extends('layouts.app')

@section('title', 'Despesas')
@section('page-title', 'Despesas')

@section('content')
@include('dono.financeiro._nav')

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Pendentes</div>
                <div class="fs-4 fw-bold text-danger">@money($resumo['pendentes'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Pagas neste mês</div>
                <div class="fs-4 fw-bold text-success">@money($resumo['pagas_mes'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted">Vencidas</div>
                <div class="fs-4 fw-bold">{{ $resumo['vencidas'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-plus text-pink me-2"></i>Nova despesa</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.financeiro.despesas.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descrição *</label>
                        <input type="text" name="descricao" maxlength="255" required
                               class="form-control @error('descricao') is-invalid @enderror"
                               value="{{ old('descricao') }}">
                        @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoria *</label>
                        <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                            @foreach($categorias as $value => $label)
                                <option value="{{ $value }}" @selected(old('categoria') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fornecedor</label>
                        <input type="text" name="fornecedor" maxlength="255"
                               class="form-control" value="{{ old('fornecedor') }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Valor *</label>
                            <input type="number" name="valor" step="0.01" min="0.01" required
                                   class="form-control @error('valor') is-invalid @enderror"
                                   value="{{ old('valor') }}">
                            @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Vencimento *</label>
                            <input type="date" name="vencimento" required
                                   class="form-control @error('vencimento') is-invalid @enderror"
                                   value="{{ old('vencimento', now()->toDateString()) }}">
                            @error('vencimento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input type="hidden" name="recorrente" value="0">
                        <input class="form-check-input" type="checkbox" name="recorrente" value="1" id="recorrente"
                               @checked(old('recorrente'))>
                        <label class="form-check-label" for="recorrente">Recorrente</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="pago" value="0">
                        <input class="form-check-input" type="checkbox" name="pago" value="1" id="pago"
                               @checked(old('pago'))>
                        <label class="form-check-label" for="pago">Já paga</label>
                    </div>
                    <button type="submit" class="btn btn-pink w-100">
                        <i class="fas fa-plus me-1"></i>Cadastrar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Contas a pagar</h5>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" data-autosubmit>
                        <option value="todas" @selected($status === 'todas')>Todas</option>
                        <option value="pendentes" @selected($status === 'pendentes')>Pendentes</option>
                        <option value="pagas" @selected($status === 'pagas')>Pagas</option>
                        <option value="vencidas" @selected($status === 'vencidas')>Vencidas</option>
                    </select>
                    <select name="categoria" class="form-select form-select-sm" data-autosubmit>
                        <option value="">Categoria</option>
                        @foreach($categorias as $value => $label)
                            <option value="{{ $value }}" @selected(request('categoria') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                @forelse($despesas as $d)
                    <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }} gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                {{ $d->descricao }}
                                @if($d->estaPaga())
                                    <span class="badge text-bg-success">Paga</span>
                                @elseif($d->estaVencida())
                                    <span class="badge text-bg-danger">Vencida</span>
                                @else
                                    <span class="badge text-bg-warning">Pendente</span>
                                @endif
                                @if($d->recorrente)
                                    <span class="badge text-bg-light text-muted border">Recorrente</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                {{ $d->categoria_label }}
                                @if($d->fornecedor) · {{ $d->fornecedor }}@endif
                                · vence {{ $d->vencimento->format('d/m/Y') }}
                                @if($d->estaPaga()) · paga em {{ $d->pago_em->format('d/m/Y') }}@endif
                            </div>
                        </div>
                        <div class="fw-bold text-nowrap">@money($d->valor)</div>
                        <div class="d-flex gap-1">
                            @unless($d->estaPaga())
                                <form method="POST" action="{{ route('dono.financeiro.despesas.pagar', $d) }}"
                                      data-confirm="Marcar como paga?"
                                      data-confirm-type="success"
                                      data-confirm-ok="Confirmar">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar paga">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endunless
                            <button type="button" class="btn btn-sm btn-ghost" title="Editar"
                                    data-bs-toggle="modal" data-bs-target="#editDespesa{{ $d->id }}">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" action="{{ route('dono.financeiro.despesas.destroy', $d) }}"
                                  data-confirm="Excluir despesa?"
                                  data-confirm-message="Esta ação não pode ser desfeita."
                                  data-confirm-ok="Excluir">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="modal fade" id="editDespesa{{ $d->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('dono.financeiro.despesas.update', $d) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar despesa</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Descrição *</label>
                                            <input type="text" name="descricao" class="form-control" required
                                                   value="{{ $d->descricao }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Categoria *</label>
                                            <select name="categoria" class="form-select" required>
                                                @foreach($categorias as $value => $label)
                                                    <option value="{{ $value }}" @selected($d->categoria === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Fornecedor</label>
                                            <input type="text" name="fornecedor" class="form-control"
                                                   value="{{ $d->fornecedor }}">
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Valor *</label>
                                                <input type="number" name="valor" step="0.01" min="0.01"
                                                       class="form-control" required value="{{ $d->valor }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Vencimento *</label>
                                                <input type="date" name="vencimento" class="form-control" required
                                                       value="{{ $d->vencimento->toDateString() }}">
                                            </div>
                                        </div>
                                        <div class="form-check">
                                            <input type="hidden" name="recorrente" value="0">
                                            <input class="form-check-input" type="checkbox" name="recorrente" value="1"
                                                   id="recorrente{{ $d->id }}" @checked($d->recorrente)>
                                            <label class="form-check-label" for="recorrente{{ $d->id }}">Recorrente</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-pink">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <p class="mb-0">Nenhuma despesa encontrada.</p>
                    </div>
                @endforelse
            </div>
            @if($despesas->hasPages())
                <div class="card-footer">{{ $despesas->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
