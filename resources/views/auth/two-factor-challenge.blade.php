@extends('layouts.auth')

@section('title', 'Verificação em duas etapas')
@section('subtitle', 'Segurança da conta')

@section('content')
<p class="text-center text-muted mb-4">
    Digite o código de 6 dígitos do seu app autenticador
    ou um código de recuperação.
</p>

@if ($errors->any())
    <div class="alert alert-danger alert-permanent">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('2fa.challenge.verify') }}">
    @csrf
    <input type="text" name="code" class="form-control form-control-lg text-center"
           maxlength="16" placeholder="000000 ou ABCD-EFGH"
           autocomplete="one-time-code" autofocus required
           style="letter-spacing:.2em;font-weight:700;">
    <button type="submit" class="btn btn-pink btn-lg w-100 mt-3">
        <i class="fas fa-shield-halved me-2"></i> Verificar
    </button>
</form>

<div class="text-center mt-3">
    <a href="{{ route('login') }}" class="text-muted small">Voltar ao login</a>
</div>
@endsection
