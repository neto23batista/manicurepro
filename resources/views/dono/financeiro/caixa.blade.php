@extends('layouts.app')

@section('title', 'Caixa operacional')
@section('page-title', 'Caixa operacional')

@section('content')
@include('dono.financeiro._nav')

@if($aberto)
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-pink h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Saldo calculado</div>
                    <div class="fs-3 fw-bold">@money($saldoCalculado)</div>
                    <div class="small opacity-75 mt-1">Aberto {{ $aberto->aberto_em->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Saldo inicial</div>
                    <div class="fs-4 fw-bold">@money($aberto->saldo_inicial)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Movimentações</div>
                    <div class="fs-4 fw-bold">{{ $aberto->movimentacoes->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted">Aberto por</div>
                    <div class="fw-semibold">{{ $aberto->abertoPor?->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-plus-minus text-pink me-2"></i>Nova movimentação</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dono.financeiro.caixa.movimentar', $aberto) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo *</label>
                            <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                                @foreach(\App\Models\CaixaMovimentacao::TIPOS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('tipo') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Valor *</label>
                            <input type="number" name="valor" step="0.01" min="0.01"
                                   class="form-control @error('valor') is-invalid @enderror"
                                   value="{{ old('valor') }}" required>
                            @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descrição *</label>
                            <input type="text" name="descricao" maxlength="255"
                                   class="form-control @error('descricao') is-invalid @enderror"
                                   value="{{ old('descricao') }}" required
                                   placeholder="Ex: Sangria para cofre, venda avulsa…">
                            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-pink w-100">
                            <i class="fas fa-check me-1"></i>Registrar
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-lock text-pink me-2"></i>Fechar caixa</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Informe o valor contado no caixa. A diferença será
                        <strong>informado − calculado</strong> (@money($saldoCalculado)).
                    </p>
                    <form method="POST" action="{{ route('dono.financeiro.caixa.fechar', $aberto) }}"
                          data-confirm="Fechar o caixa?"
                          data-confirm-message="Após o fechamento não será possível registrar novas movimentações."
                          data-confirm-ok="Fechar">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Saldo contado *</label>
                            <input type="number" name="saldo_final_informado" step="0.01" min="0"
                                   class="form-control @error('saldo_final_informado') is-invalid @enderror"
                                   value="{{ old('saldo_final_informado', $saldoCalculado) }}" required>
                            @error('saldo_final_informado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observação</label>
                            <textarea name="observacao" rows="2" maxlength="500"
                                      class="form-control @error('observacao') is-invalid @enderror"
                                      placeholder="Opcional">{{ old('observacao', $aberto->observacao) }}</textarea>
                            @error('observacao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-lock me-1"></i>Fechar caixa
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Movimentações do dia</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($aberto->movimentacoes->sortByDesc('created_at') as $mov)
                        <div class="d-flex align-items-center justify-content-between p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <span class="badge text-bg-{{ $mov->isCredito() ? 'success' : 'danger' }} me-1">
                                    {{ $mov->tipo_label }}
                                </span>
                                <span class="fw-semibold">{{ $mov->descricao }}</span>
                                <div class="small text-muted">
                                    {{ $mov->created_at->format('H:i') }}
                                    @if($mov->user) · {{ $mov->user->name }}@endif
                                </div>
                            </div>
                            <div class="fw-bold {{ $mov->isCredito() ? 'text-success' : 'text-danger' }}">
                                {{ $mov->isCredito() ? '+' : '−' }}@money($mov->valor)
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-4">
                            <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
                            <p class="mb-0">Nenhuma movimentação ainda.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-unlock text-pink me-2"></i>Abrir caixa</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dono.financeiro.caixa.abrir') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Saldo inicial *</label>
                            <input type="number" name="saldo_inicial" step="0.01" min="0"
                                   class="form-control @error('saldo_inicial') is-invalid @enderror"
                                   value="{{ old('saldo_inicial', '0.00') }}" required>
                            @error('saldo_inicial') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('caixa') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observação</label>
                            <textarea name="observacao" rows="2" maxlength="500"
                                      class="form-control">{{ old('observacao') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-pink w-100">
                            <i class="fas fa-unlock me-1"></i>Abrir caixa
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-vault"></i></div>
                        <h6 class="fw-bold">Nenhum caixa aberto</h6>
                        <p class="mb-0">Abra o caixa para registrar entradas, saídas, sangrias e suprimentos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-clock-rotate-left text-pink me-2"></i>Histórico de caixas</h5>
    </div>
    <div class="card-body p-0">
        @if($historico->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Abertura</th>
                            <th>Fechamento</th>
                            <th class="text-end">Inicial</th>
                            <th class="text-end">Calculado</th>
                            <th class="text-end">Informado</th>
                            <th class="text-end">Diferença</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historico as $h)
                            <tr>
                                <td>
                                    <div>{{ $h->aberto_em->format('d/m/Y H:i') }}</div>
                                    <small class="text-muted">{{ $h->abertoPor?->name }}</small>
                                </td>
                                <td>
                                    @if($h->fechado_em)
                                        <div>{{ $h->fechado_em->format('d/m/Y H:i') }}</div>
                                        <small class="text-muted">{{ $h->fechadoPor?->name }}</small>
                                    @else
                                        <span class="badge text-bg-success">Aberto</span>
                                    @endif
                                </td>
                                <td class="text-end">@money($h->saldo_inicial)</td>
                                <td class="text-end">
                                    @if($h->saldo_calculado !== null) @money($h->saldo_calculado) @else — @endif
                                </td>
                                <td class="text-end">
                                    @if($h->saldo_final_informado !== null) @money($h->saldo_final_informado) @else — @endif
                                </td>
                                <td class="text-end">
                                    @if($h->diferenca !== null)
                                        <span class="{{ (float)$h->diferenca === 0.0 ? 'text-muted' : ((float)$h->diferenca > 0 ? 'text-success' : 'text-danger') }}">
                                            @money($h->diferenca)
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('dono.financeiro.caixa.show', $h) }}" class="btn btn-sm btn-ghost" title="Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state py-4">
                <div class="empty-state-icon"><i class="fas fa-clock-rotate-left"></i></div>
                <p class="mb-0">Nenhum caixa registrado ainda.</p>
            </div>
        @endif
    </div>
</div>
@endsection
