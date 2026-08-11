@extends('layouts.app')

@section('title', 'Pacotes')
@section('page-title', 'Pacotes / Combos')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-layer-group text-pink me-2"></i>Pacotes</h5>
        <a href="{{ route('dono.pacotes.create') }}" class="btn btn-pink">
            <i class="fas fa-plus me-1"></i> Novo pacote
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Sessões</th>
                        <th>Validade</th>
                        <th>Preço</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pacotes as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->nome }}</td>
                            <td>{{ $p->sessoes }}x</td>
                            <td>{{ $p->validade_dias ? $p->validade_dias . ' dias' : 'Sem prazo' }}</td>
                            <td class="fw-bold">R$ {{ number_format($p->preco, 2, ',', '.') }}</td>
                            <td>
                                @if($p->ativo)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('dono.pacotes.edit', $p) }}" class="btn btn-sm btn-ghost" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('dono.pacotes.destroy', $p) }}" method="POST" class="d-inline"
                                      data-confirm="Excluir pacote?" data-confirm-message="O pacote {{ $p->nome }} será excluído.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-5">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
                                    <h6 class="fw-bold">Nenhum pacote cadastrado</h6>
                                    <p>Crie pacotes de sessões para vender aos clientes.</p>
                                    <a href="{{ route('dono.pacotes.create') }}" class="btn btn-pink mt-2">
                                        <i class="fas fa-plus me-1"></i> Criar primeiro pacote
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pacotes->hasPages())
        <div class="card-footer">{{ $pacotes->links() }}</div>
    @endif
</div>
@endsection
