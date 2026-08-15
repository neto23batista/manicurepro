@extends('layouts.auth')

@section('title', 'Recuperar senha')
@section('subtitle', 'Vamos te ajudar a entrar')

@section('content')
<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <x-honeypot />

    <x-form-errors class="mb-3" />

    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;background:var(--pink-100);color:var(--pink-600);border-radius:50%;font-size:24px"
             aria-hidden="true">
            <i class="fa-solid fa-key"></i>
        </div>
        <h1 class="h5 fw-bold">Esqueceu sua senha?</h1>
        <p class="text-muted small mb-0">Informe seu e-mail e enviaremos um link para você criar uma nova senha.</p>
    </div>

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">
            <i class="fas fa-envelope me-1 text-pink" aria-hidden="true"></i> E-mail
        </label>
        <input type="email"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               id="email"
               name="email"
               value="{{ old('email') }}"
               placeholder="seu@email.com"
               required
               autofocus
               autocomplete="email"
               aria-required="true"
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')
            <div class="invalid-feedback" id="email-error">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i> Enviar link de recuperação
    </button>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="text-pink fw-semibold">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Voltar para login
        </a>
    </div>
</form>
@endsection
