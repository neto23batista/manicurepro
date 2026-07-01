@extends('layouts.app')

@section('title', 'Novo Usuário')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fa-solid fa-user-plus me-2 text-pink"></i>Novo Usuário</h2>
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="{{ route('admin.usuarios.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="Nome do usuário" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="usuario@email.com" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Perfil <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">Selecione o perfil</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
                        <option value="dono" {{ old('role') == 'dono' ? 'selected' : '' }}>Dono de Salão</option>
                        <option value="atendente" {{ old('role') == 'atendente' ? 'selected' : '' }}>Atendente</option>
                        <option value="manicure" {{ old('role') == 'manicure' ? 'selected' : '' }}>Manicure</option>
                        <option value="cliente" {{ old('role') == 'cliente' ? 'selected' : '' }}>Cliente</option>
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Mínimo 8 caracteres" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Confirmar Senha <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation"
                           class="form-control"
                           placeholder="Repita a senha" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="(00) 00000-0000">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Salão Vinculado</label>
                    <select name="salao_id" class="form-select @error('salao_id') is-invalid @enderror">
                        <option value="">— Sem vínculo —</option>
                        @foreach($saloes as $s)
                            <option value="{{ $s->id }}" {{ old('salao_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->nome }}
                            </option>
                        @endforeach
                    </select>
                    @error('salao_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Obrigatório para dono, atendente, manicure e cliente.</small>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ativo">Usuário ativo</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pink">
                    <i class="fa-solid fa-user-plus me-1"></i>Criar Usuário
                </button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
