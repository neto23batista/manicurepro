@extends('layouts.app')

@section('title', 'Inventário')
@section('page-title', 'Inventário de estoque')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0"><i class="fas fa-clipboard-check text-pink me-2"></i>Contagem física</h5>
            <small class="text-muted">Informe a quantidade contada. Diferenças geram ajustes auditados.</small>
        </div>
        <a href="{{ route('dono.estoque.relatorio') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-chart-bar me-1"></i>Relatório
        </a>
    </div>

    @if($produtos->isEmpty())
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
                <h6 class="fw-bold">Nenhum produto ativo</h6>
                <p>Cadastre produtos antes de fazer inventário.</p>
                <a href="{{ route('dono.produtos.create') }}" class="btn btn-pink btn-sm">Novo produto</a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('dono.estoque.inventario.store') }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-center">Sistema</th>
                            <th style="width:160px">Contagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($produtos as $produto)
                            @php($qtd = rtrim(rtrim(number_format($produto->estoque_atual, 3, ',', '.'), '0'), ','))
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $produto->nome }}</div>
                                    <small class="text-muted">{{ $produto->unidade }}</small>
                                </td>
                                <td class="text-center fw-semibold {{ $produto->estoque_baixo ? 'text-danger' : '' }}">
                                    {{ $qtd }}
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0" name="contagens[{{ $produto->id }}]"
                                           class="form-control form-control-sm"
                                           value="{{ old('contagens.'.$produto->id, number_format((float) $produto->estoque_atual, 3, '.', '')) }}"
                                           required>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('dono.produtos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-pink"
                        data-confirm="Aplicar inventário?"
                        data-confirm-message="Diferenças serão registradas como ajustes de estoque."
                        data-confirm-ok="Aplicar">
                    <i class="fas fa-check me-1"></i>Aplicar inventário
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
