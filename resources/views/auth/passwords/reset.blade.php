@extends('layouts.auth')

@section('title', 'Nova senha')
@section('subtitle', 'Quase lá')

@section('content')
<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <x-honeypot />
    <input type="hidden" name="token" value="{{ $token }}">

    <x-form-errors class="mb-3" />

    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;background:var(--pink-100);color:var(--pink-600);border-radius:50%;font-size:24px"
             aria-hidden="true">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h1 class="h5 fw-bold">Crie sua nova senha</h1>
        <p class="text-muted small mb-0">Escolha algo que você lembre, com pelo menos 8 caracteres.</p>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">E-mail</label>
        <input type="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               name="email"
               value="{{ $email ?? old('email') }}"
               required
               readonly
               autocomplete="email"
               style="background:var(--ink-50)"
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')
            <div class="invalid-feedback" id="email-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Nova senha</label>
        <input type="password"
               id="password"
               class="form-control form-control-lg @error('password') is-invalid @enderror"
               name="password"
               required
               autofocus
               autocomplete="new-password"
               placeholder="••••••••"
               aria-required="true"
               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        @error('password')
            <div class="invalid-feedback" id="password-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold">Confirmar nova senha</label>
        <input type="password"
               id="password_confirmation"
               class="form-control form-control-lg"
               name="password_confirmation"
               required
               autocomplete="new-password"
               placeholder="••••••••"
               aria-required="true">
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-check me-2" aria-hidden="true"></i> Redefinir senha
    </button>
</form>
@endsection
