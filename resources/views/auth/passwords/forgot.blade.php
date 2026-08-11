@extends('layouts.auth')

@section('title', 'Recuperar senha')
@section('subtitle', 'Vamos te ajudar a entrar')

@section('content')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <x-honeypot />

    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;background:var(--pink-100);color:var(--pink-600);border-radius:50%;font-size:24px">
            <i class="fa-solid fa-key"></i>
        </div>
        <h5 class="fw-bold">Esqueceu sua senha?</h5>
        <p class="text-muted small mb-0">Informe seu e-mail e enviaremos um link para você criar uma nova senha.</p>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold">
            <i class="fas fa-envelope me-1 text-pink"></i> E-mail
        </label>
        <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
               name="email" value="{{ old('email') }}" placeholder="seu@email.com" required autofocus>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-paper-plane me-2"></i> Enviar link de recuperação
    </button>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="text-pink fw-semibold">
            <i class="fas fa-arrow-left me-1"></i>Voltar para login
        </a>
    </div>
</form>
@endsection
