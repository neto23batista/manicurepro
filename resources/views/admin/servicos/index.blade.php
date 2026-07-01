@extends('layouts.app')

@section('title', 'Serviços')
@section('page-title', 'Gerenciar Serviços')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-spa text-pink me-2"></i> Serviços</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.servicos.create') }}" class="btn btn-pink btn-sm">
                <i class="fas fa-plus me-1"></i> Novo
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Serviço</th>
                        <th>Salão</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Duração</th>
                        <th>Combo</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($servicos as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td><div class="fw-semibold">{{ $s->nome }}</div></td>
                            <td>{{ $s->salao?->nome }}</td>
                            <td><span class="badge bg-secondary">{{ $s->categoria?->nome ?? '—' }}</span></td>
                            <td class="fw-bold text-pink">{{ $s->preco_formatado }}</td>
                            <td>{{ $s->duracao_formatada }}</td>
                            <td>
                                @if($s->combo)
                                    <span class="badge bg-pink">Combo</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $s->ativo ? 'bg-success' : 'bg-danger' }}">
                                    {{ $s->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.servicos.edit', $s) }}" class="btn btn-sm btn-ghost">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Nenhum serviço cadastrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($servicos->hasPages())
        <div class="card-footer">{{ $servicos->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
