@extends('layouts.auth')

@section('title', 'Criar Conta')
@section('subtitle', 'Crie sua conta gratuita')

@section('content')
<form method="POST" action="{{ route('register.post') }}">
    @csrf
    <x-honeypot />

    @if($salao ?? null)
        <div class="alert alert-pink mb-4">
            <i class="fas fa-store me-2"></i>
            Criando sua conta em <strong>{{ $salao->nome }}</strong>
        </div>
    @endif

    <div class="mb-3">
        <label for="name" class="form-label fw-semibold">
            <i class="fas fa-user me-1 text-pink"></i> Nome Completo
        </label>
        <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror"
               id="name" name="name" value="{{ old('name') }}" placeholder="Seu nome" required autofocus>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">
            <i class="fas fa-envelope me-1 text-pink"></i> E-mail
        </label>
        <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label fw-semibold">
            <i class="fas fa-phone me-1 text-pink"></i> Telefone
        </label>
        <input type="tel" class="form-control form-control-lg @error('phone') is-invalid @enderror"
               id="phone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(config('manicure.indicacao.enabled', true))
    <div class="mb-3">
        <label for="codigo_indicacao" class="form-label fw-semibold">
            <i class="fas fa-gift me-1 text-pink"></i> Código de indicação
            <span class="text-muted fw-normal">(opcional)</span>
        </label>
        <input type="text" class="form-control form-control-lg @error('codigo_indicacao') is-invalid @enderror"
               id="codigo_indicacao" name="codigo_indicacao"
               value="{{ old('codigo_indicacao', request('ref')) }}"
               placeholder="Código da amiga" maxlength="16" autocomplete="off">
        @error('codigo_indicacao')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    @endif

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">
            <i class="fas fa-lock me-1 text-pink"></i> Senha
        </label>
        <div class="input-group">
            <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                   id="password" name="password" placeholder="Mínimo 8 caracteres" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold">
            <i class="fas fa-lock me-1 text-pink"></i> Confirmar Senha
        </label>
        <input type="password" class="form-control form-control-lg"
               id="password_confirmation" name="password_confirmation" placeholder="Repita a senha" required>
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-user-plus me-2"></i> Criar Conta
    </button>

    <div class="text-center mt-4">
        <p class="mb-0 text-muted">Já tem conta?
            <a href="{{ route('login') }}" class="text-pink fw-semibold">Fazer login</a>
        </p>
    </div>
</form>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>
@endsection
