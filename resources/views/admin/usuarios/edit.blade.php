@extends('layouts.app')

@section('title', 'Editar Usuário')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fa-solid fa-user-pen me-2 text-pink"></i>Editar Usuário</h2>
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $usuario->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $usuario->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Perfil <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="admin"     {{ old('role', $usuario->role) == 'admin'     ? 'selected' : '' }}>Administrador</option>
                                <option value="dono"      {{ old('role', $usuario->role) == 'dono'      ? 'selected' : '' }}>Dono de Salão</option>
                                <option value="atendente" {{ old('role', $usuario->role) == 'atendente' ? 'selected' : '' }}>Atendente</option>
                                <option value="manicure"  {{ old('role', $usuario->role) == 'manicure'  ? 'selected' : '' }}>Manicure</option>
                                <option value="cliente"   {{ old('role', $usuario->role) == 'cliente'   ? 'selected' : '' }}>Cliente</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $usuario->phone) }}" placeholder="(00) 00000-0000">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Salão Vinculado</label>
                            <select name="salao_id" class="form-select @error('salao_id') is-invalid @enderror">
                                <option value="">— Sem vínculo —</option>
                                @foreach($saloes as $s)
                                    <option value="{{ $s->id }}"
                                        {{ old('salao_id', $usuario->salao_id) == $s->id ? 'selected' : '' }}>
                                        {{ $s->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('salao_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <hr>
                            <p class="text-muted small mb-2">
                                <i class="fa-solid fa-lock me-1"></i>
                                Deixe a senha em branco para manter a senha atual.
                            </p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nova Senha</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Deixe em branco para não alterar">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation"
                                   class="form-control"
                                   placeholder="Repita a nova senha">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="ativo" value="0">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                                       {{ old('ativo', $usuario->ativo ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="ativo">Usuário ativo</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Salvar Alterações
                        </button>
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>

                        @if($usuario->id !== auth()->id())
                        <form action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST" class="ms-auto"
                              data-confirm="Desativar usuário?" data-confirm-message="O usuário será desativado e poderá ser reativado depois." data-confirm-ok="Desativar">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fa-solid fa-user-slash me-1"></i>Desativar
                            </button>
                        </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm text-center">
            <div class="card-body py-4">
                <img src="{{ $usuario->avatar_url }}" alt="{{ $usuario->name }}"
                     class="rounded-circle mb-3 border border-3 border-pink"
                     width="90" height="90">
                <h5 class="fw-bold mb-1">{{ $usuario->name }}</h5>
                <span class="badge bg-pink mb-2">{{ ucfirst($usuario->role) }}</span>
                <p class="text-muted small mb-0">{{ $usuario->email }}</p>
                <p class="text-muted small">Cadastrado em {{ $usuario->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-circle-info me-2 text-pink"></i>Info</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted small">Atualizado em</span>
                    <span class="small fw-semibold">{{ $usuario->updated_at->format('d/m/Y H:i') }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted small">E-mail verificado</span>
                    <span class="small">
                        @if($usuario->email_verified_at)
                            <i class="fa-solid fa-circle-check text-success"></i> Sim
                        @else
                            <i class="fa-solid fa-circle-xmark text-danger"></i> Não
                        @endif
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted small">Status</span>
                    <span class="badge {{ ($usuario->ativo ?? true) ? 'bg-success' : 'bg-secondary' }}">
                        {{ ($usuario->ativo ?? true) ? 'Ativo' : 'Inativo' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
