@extends('layouts.app')

@section('title', 'Produtos')
@section('page-title', 'Produtos & Estoque')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-box text-pink me-2"></i>Produtos</h5>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex">
                <input type="search" name="busca" value="{{ request('busca') }}"
                       class="form-control form-control-sm" placeholder="Buscar produto…" style="min-width:180px">
            </form>
            <a href="{{ route('dono.estoque.inventario.create') }}" class="btn btn-outline-secondary btn-sm" title="Inventário">
                <i class="fas fa-clipboard-check"></i>
            </a>
            <a href="{{ route('dono.estoque.relatorio') }}" class="btn btn-outline-secondary btn-sm" title="Relatório">
                <i class="fas fa-chart-bar"></i>
            </a>
            <a href="{{ route('dono.produtos.create') }}" class="btn btn-pink btn-sm">
                <i class="fas fa-plus me-1"></i>Novo produto
            </a>
        </div>
    </div>

    @if($baixoEstoque > 0)
        <div class="alert alert-warning m-3 mb-0">
            <i class="fas fa-triangle-exclamation me-2"></i>
            <strong>{{ $baixoEstoque }}</strong> produto(s) com estoque no mínimo ou zerado.
        </div>
    @endif

    <div class="card-body p-0">
        @forelse($produtos as $produto)
            @php($qtd = rtrim(rtrim(number_format($produto->estoque_atual, 3, ',', '.'), '0'), ','))
            <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }} {{ !$produto->ativo ? 'opacity-50' : '' }}">
                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        {{ $produto->nome }}
                        @unless($produto->ativo)<span class="badge bg-secondary ms-1">Inativo</span>@endunless
                    </div>
                    <small class="text-muted">
                        {{ $produto->marca ?: 'Sem marca' }}@if($produto->codigo) · cód. {{ $produto->codigo }}@endif
                        @if($produto->fornecedor) · {{ $produto->fornecedor->nome }}@endif
                    </small>
                </div>
                <div class="text-center px-3 d-none d-md-block">
                    <div class="text-muted small">Venda</div>
                    <div class="fw-semibold">@money($produto->preco_venda)</div>
                </div>
                <div class="text-center px-3">
                    <div class="text-muted small">Estoque</div>
                    <div class="fw-bold {{ $produto->estoque_baixo ? 'text-danger' : '' }}">
                        {{ $qtd }} {{ $produto->unidade }}
                        @if($produto->estoque_baixo)<i class="fas fa-triangle-exclamation ms-1" aria-hidden="true"></i>@endif
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-ghost" title="Movimentar estoque"
                            data-bs-toggle="modal" data-bs-target="#mov{{ $produto->id }}">
                        <i class="fas fa-right-left"></i>
                    </button>
                    <a href="{{ route('dono.produtos.edit', $produto) }}" class="btn btn-sm btn-ghost" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($produto->ativo)
                        <form method="POST" action="{{ route('dono.produtos.destroy', $produto) }}"
                              data-confirm="Desativar produto?" data-confirm-message="{{ $produto->nome }} ficará indisponível." data-confirm-ok="Desativar">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost text-danger" title="Desativar"><i class="fas fa-ban"></i></button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Modal: movimentação de estoque --}}
            <div class="modal fade" id="mov{{ $produto->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Movimentar estoque</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <form method="POST" action="{{ route('dono.produtos.estoque', $produto) }}">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    {{ $produto->nome }} — atual: <strong>{{ $qtd }} {{ $produto->unidade }}</strong>
                                </p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tipo</label>
                                    <select name="tipo" class="form-select js-estoque-tipo" required>
                                        <option value="entrada">Entrada (+) — reposição/compra</option>
                                        <option value="saida">Saída (−) — venda/uso</option>
                                        <option value="ajuste">Ajuste — definir o total exato</option>
                                        <option value="perda">Perda (−) — motivo obrigatório</option>
                                        <option value="consumo_interno">Consumo interno (−) — motivo obrigatório</option>
                                        <option value="devolucao">Devolução (+) — motivo obrigatório</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Quantidade</label>
                                    <input type="number" step="0.001" min="0.001" name="quantidade" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Motivo <small class="text-muted js-motivo-hint">(opcional)</small></label>
                                    <input type="text" name="motivo" class="form-control" maxlength="255" placeholder="Ex: Compra no fornecedor">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-pink">Salvar movimentação</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
                <h6 class="fw-bold">Nenhum produto cadastrado</h6>
                <p>Cadastre seus produtos para controlar estoque e vendas.</p>
                <a href="{{ route('dono.produtos.create') }}" class="btn btn-pink btn-sm">
                    <i class="fas fa-plus me-1"></i>Novo produto
                </a>
            </div>
        @endforelse
    </div>

    @if($produtos->hasPages())
        <div class="card-footer">{{ $produtos->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-estoque-tipo').forEach(function (sel) {
    sel.addEventListener('change', function () {
        var precisa = ['perda', 'consumo_interno', 'devolucao'].includes(sel.value);
        var form = sel.closest('form');
        var motivo = form.querySelector('[name="motivo"]');
        var hint = form.querySelector('.js-motivo-hint');
        if (motivo) motivo.required = precisa;
        if (hint) hint.textContent = precisa ? '(obrigatório)' : '(opcional)';
    });
});
</script>
@endpush
