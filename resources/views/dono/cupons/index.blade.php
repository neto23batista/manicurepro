@extends('layouts.app')

@section('title', 'Cupons')
@section('page-title', 'Cupons de Desconto')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-ticket text-pink me-2"></i>Cupons</h5>
        <a href="{{ route('dono.cupons.create') }}" class="btn btn-pink">
            <i class="fas fa-plus me-1"></i> Novo cupom
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Mínimo</th>
                        <th>Usos</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cupons as $c)
                        <tr>
                            <td><code class="text-pink fw-bold fs-6">{{ $c->codigo }}</code></td>
                            <td>
                                @if($c->tipo === 'percentual')
                                    <span class="badge bg-info">% Percentual</span>
                                @else
                                    <span class="badge bg-warning">R$ Fixo</span>
                                @endif
                            </td>
                            <td class="fw-bold">
                                @if($c->tipo === 'percentual')
                                    {{ number_format($c->valor, 0) }}%
                                @else
                                    R$ {{ number_format($c->valor, 2, ',', '.') }}
                                @endif
                            </td>
                            <td>{{ $c->minimo_pedido ? 'R$ ' . number_format($c->minimo_pedido, 2, ',', '.') : '—' }}</td>
                            <td>
                                <span class="text-pink fw-semibold">{{ $c->uso_atual }}</span>
                                @if($c->uso_maximo) / {{ $c->uso_maximo }} @else / ∞ @endif
                            </td>
                            <td>{{ $c->validade ? $c->validade->format('d/m/Y') : 'Sem prazo' }}</td>
                            <td>
                                @if($c->isValido())
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Válido</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('dono.cupons.edit', $c) }}" class="btn btn-sm btn-ghost" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('dono.cupons.destroy', $c) }}" method="POST" class="d-inline"
                                      data-confirm="Excluir cupom?" data-confirm-message="O cupom {{ $c->codigo }} será excluído permanentemente.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-5">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fas fa-ticket"></i></div>
                                    <h6 class="fw-bold">Nenhum cupom cadastrado</h6>
                                    <p>Crie cupons para aumentar o engajamento dos seus clientes!</p>
                                    <a href="{{ route('dono.cupons.create') }}" class="btn btn-pink mt-2">
                                        <i class="fas fa-plus me-1"></i> Criar primeiro cupom
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cupons->hasPages())
        <div class="card-footer">{{ $cupons->links() }}</div>
    @endif
</div>
@endsection
