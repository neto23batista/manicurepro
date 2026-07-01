@extends('layouts.app')

@section('title', 'Salões')
@section('page-title', 'Gerenciar Salões')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-store text-pink me-2"></i> Todos os Salões</h5>
        <a href="{{ route('admin.saloes.create') }}" class="btn btn-pink">
            <i class="fas fa-plus me-1"></i> Novo Salão
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Manicures</th>
                        <th>Clientes</th>
                        <th>Agendamentos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($saloes as $salao)
                        <tr>
                            <td>{{ $salao->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $salao->nome }}</div>
                                <small class="text-muted">{{ $salao->slug }}</small>
                            </td>
                            <td>{{ $salao->cidade }}/{{ $salao->estado }}</td>
                            <td><span class="badge bg-pink">{{ $salao->manicures_count }}</span></td>
                            <td><span class="badge bg-blue">{{ $salao->clientes_count }}</span></td>
                            <td><span class="badge bg-secondary">{{ $salao->agendamentos_count }}</span></td>
                            <td>
                                <span class="badge {{ $salao->ativo ? 'bg-success' : 'bg-danger' }}">
                                    {{ $salao->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.saloes.show', $salao) }}" class="btn btn-ghost" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.saloes.edit', $salao) }}" class="btn btn-ghost" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-store fa-2x mb-2 d-block"></i>
                                Nenhum salão cadastrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($saloes->hasPages())
        <div class="card-footer">{{ $saloes->links() }}</div>
    @endif
</div>
@endsection
