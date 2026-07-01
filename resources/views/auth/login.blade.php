@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Acesse sua conta')

@section('content')
<form method="POST" action="{{ route('login.post') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">
            <i class="fas fa-envelope me-1 text-pink"></i> E-mail
        </label>
        <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="form-label fw-semibold">
            <i class="fas fa-lock me-1 text-pink"></i> Senha
        </label>
        <div class="input-group">
            <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                   id="password" name="password" placeholder="••••••••" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Lembrar-me</label>
        </div>
        <a href="{{ route('password.request') }}" class="text-pink small fw-semibold text-decoration-none">
            Esqueceu a senha?
        </a>
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-sign-in-alt me-2"></i> Entrar
    </button>

    <div class="text-center mt-4">
        <p class="mb-0 text-muted">Não tem conta?
            <a href="{{ route('register') }}" class="text-pink fw-semibold">Criar conta</a>
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
