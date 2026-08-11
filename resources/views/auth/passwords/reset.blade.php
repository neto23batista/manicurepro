@extends('layouts.auth')

@section('title', 'Nova senha')
@section('subtitle', 'Quase lá')

@section('content')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <x-honeypot />
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;background:var(--pink-100);color:var(--pink-600);border-radius:50%;font-size:24px">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h5 class="fw-bold">Crie sua nova senha</h5>
        <p class="text-muted small mb-0">Escolha algo que você lembre, com pelo menos 8 caracteres.</p>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">E-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               name="email" value="{{ $email ?? old('email') }}" required readonly style="background:var(--ink-50)">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Nova senha</label>
        <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
               name="password" required autofocus autocomplete="new-password" placeholder="••••••••">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold">Confirmar nova senha</label>
        <input type="password" class="form-control form-control-lg"
               name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-check me-2"></i> Redefinir senha
    </button>
</form>
@endsection
