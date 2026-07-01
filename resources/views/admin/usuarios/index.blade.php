@extends('layouts.app')

@section('title', 'Usuários')
@section('page-title', 'Gerenciar Usuários')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-users text-pink me-2"></i> Todos os Usuários</h5>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
                <select name="role" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todos os perfis</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="dono" {{ request('role') === 'dono' ? 'selected' : '' }}>Dono</option>
                    <option value="atendente" {{ request('role') === 'atendente' ? 'selected' : '' }}>Atendente</option>
                    <option value="manicure" {{ request('role') === 'manicure' ? 'selected' : '' }}>Manicure</option>
                    <option value="cliente" {{ request('role') === 'cliente' ? 'selected' : '' }}>Cliente</option>
                </select>
                <button type="submit" class="btn btn-pink btn-sm">Filtrar</button>
            </form>
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-pink btn-sm">
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
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Salão</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $u->avatar_url }}" width="32" height="32" class="rounded-circle">
                                    <span class="fw-semibold">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <span class="badge bg-pink">{{ ucfirst($u->role) }}</span>
                            </td>
                            <td>{{ $u->salao?->nome ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $u->ativo ? 'bg-success' : 'bg-danger' }}">
                                    {{ $u->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                @if($u->id !== auth()->id())
                                    <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-sm btn-ghost">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum usuário encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div class="card-footer">{{ $users->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
