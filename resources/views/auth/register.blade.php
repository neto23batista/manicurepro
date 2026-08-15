@extends('layouts.auth')

@section('title', 'Criar Conta')
@section('subtitle', 'Crie sua conta gratuita')

@section('content')
<form method="POST" action="{{ route('register.post') }}" novalidate>
    @csrf
    <x-honeypot />

    <x-form-errors class="mb-3" />

    @if($salao ?? null)
        <div class="alert alert-pink mb-4" role="status">
            <i class="fas fa-store me-2" aria-hidden="true"></i>
            Criando sua conta em <strong>{{ $salao->nome }}</strong>
        </div>
    @endif

    <div class="mb-3">
        <label for="name" class="form-label fw-semibold">
            <i class="fas fa-user me-1 text-pink" aria-hidden="true"></i> Nome Completo
        </label>
        <input type="text"
               class="form-control form-control-lg @error('name') is-invalid @enderror"
               id="name"
               name="name"
               value="{{ old('name') }}"
               placeholder="Seu nome"
               required
               autofocus
               autocomplete="name"
               aria-required="true"
               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name')
            <div class="invalid-feedback" id="name-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
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
               autocomplete="email"
               aria-required="true"
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')
            <div class="invalid-feedback" id="email-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label fw-semibold">
            <i class="fas fa-phone me-1 text-pink" aria-hidden="true"></i> Telefone
        </label>
        <input type="tel"
               class="form-control form-control-lg @error('phone') is-invalid @enderror"
               id="phone"
               name="phone"
               value="{{ old('phone') }}"
               placeholder="(11) 99999-9999"
               autocomplete="tel"
               @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
        @error('phone')
            <div class="invalid-feedback" id="phone-error">{{ $message }}</div>
        @enderror
    </div>

    @if(config('manicure.indicacao.enabled', true))
    <div class="mb-3">
        <label for="codigo_indicacao" class="form-label fw-semibold">
            <i class="fas fa-gift me-1 text-pink" aria-hidden="true"></i> Código de indicação
            <span class="text-muted fw-normal">(opcional)</span>
        </label>
        <input type="text"
               class="form-control form-control-lg @error('codigo_indicacao') is-invalid @enderror"
               id="codigo_indicacao"
               name="codigo_indicacao"
               value="{{ old('codigo_indicacao', request('ref')) }}"
               placeholder="Código da amiga"
               maxlength="16"
               autocomplete="off"
               @error('codigo_indicacao') aria-invalid="true" aria-describedby="codigo_indicacao-error" @enderror>
        @error('codigo_indicacao')
            <div class="invalid-feedback" id="codigo_indicacao-error">{{ $message }}</div>
        @enderror
    </div>
    @endif

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">
            <i class="fas fa-lock me-1 text-pink" aria-hidden="true"></i> Senha
        </label>
        <div class="input-group">
            <input type="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                   id="password"
                   name="password"
                   placeholder="Mínimo 8 caracteres"
                   required
                   autocomplete="new-password"
                   aria-required="true"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <button class="btn btn-outline-secondary"
                    type="button"
                    id="togglePassword"
                    aria-label="Mostrar senha"
                    aria-controls="password"
                    aria-pressed="false">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger small mt-1" id="password-error" role="alert">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold">
            <i class="fas fa-lock me-1 text-pink" aria-hidden="true"></i> Confirmar Senha
        </label>
        <input type="password"
               class="form-control form-control-lg"
               id="password_confirmation"
               name="password_confirmation"
               placeholder="Repita a senha"
               required
               autocomplete="new-password"
               aria-required="true">
    </div>

    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fas fa-user-plus me-2" aria-hidden="true"></i> Criar Conta
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
    const showing = pwd.type === 'password';
    pwd.type = showing ? 'text' : 'password';
    icon.classList.replace(showing ? 'fa-eye' : 'fa-eye-slash', showing ? 'fa-eye-slash' : 'fa-eye');
    this.setAttribute('aria-pressed', showing ? 'true' : 'false');
    this.setAttribute('aria-label', showing ? 'Ocultar senha' : 'Mostrar senha');
});
</script>
@endsection
