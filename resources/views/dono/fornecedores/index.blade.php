@extends('layouts.app')

@section('title', 'Fornecedores')
@section('page-title', 'Fornecedores')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-truck text-pink me-2"></i>Fornecedores</h5>
        <a href="{{ route('dono.fornecedores.create') }}" class="btn btn-pink btn-sm">
            <i class="fas fa-plus me-1"></i>Novo fornecedor
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Telefone</th>
                        <th>Produtos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fornecedores as $f)
                        <tr class="{{ !$f->ativo ? 'opacity-50' : '' }}">
                            <td class="fw-semibold">{{ $f->nome }}</td>
                            <td>{{ $f->contato ?: '—' }}</td>
                            <td>{{ $f->telefone ?: '—' }}</td>
                            <td>{{ $f->produtos_count }}</td>
                            <td>
                                @if($f->ativo)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('dono.fornecedores.edit', $f) }}" class="btn btn-sm btn-ghost" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($f->ativo)
                                    <form action="{{ route('dono.fornecedores.destroy', $f) }}" method="POST" class="d-inline"
                                          data-confirm="Desativar fornecedor?" data-confirm-message="{{ $f->nome }} ficará inativo.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost text-danger" title="Desativar">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-5">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fas fa-truck"></i></div>
                                    <h6 class="fw-bold">Nenhum fornecedor</h6>
                                    <p>Cadastre fornecedores para vincular aos produtos.</p>
                                    <a href="{{ route('dono.fornecedores.create') }}" class="btn btn-pink btn-sm mt-2">
                                        <i class="fas fa-plus me-1"></i>Novo fornecedor
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($fornecedores->hasPages())
        <div class="card-footer">{{ $fornecedores->links() }}</div>
    @endif
</div>
@endsection
