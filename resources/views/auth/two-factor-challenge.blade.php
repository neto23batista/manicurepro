@extends('layouts.auth')

@section('title', 'Verificação em duas etapas')
@section('subtitle', 'Segurança da conta')

@section('content')
<p class="text-center text-muted mb-4" id="2fa-help">
    Digite o código de 6 dígitos do seu app autenticador
    ou um código de recuperação.
</p>

<x-form-errors class="mb-3 alert-permanent" />

<form method="POST" action="{{ route('2fa.challenge.verify') }}" novalidate>
    @csrf
    <label for="code" class="visually-hidden">Código de verificação</label>
    <input type="text"
           id="code"
           name="code"
           class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
           maxlength="16"
           placeholder="000000 ou ABCD-EFGH"
           autocomplete="one-time-code"
           autofocus
           required
           aria-required="true"
           aria-describedby="2fa-help"
           inputmode="text"
           style="letter-spacing:.2em;font-weight:700;"
           @error('code') aria-invalid="true" @enderror>
    <button type="submit" class="btn btn-pink btn-lg w-100 mt-3">
        <i class="fas fa-shield-halved me-2" aria-hidden="true"></i> Verificar
    </button>
</form>

<div class="text-center mt-3">
    <a href="{{ route('login') }}" class="text-muted small">Voltar ao login</a>
</div>
@endsection
