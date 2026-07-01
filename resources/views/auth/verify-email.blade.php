@extends('layouts.auth')

@section('title', 'Verifique seu e-mail')
@section('subtitle', 'Quase lá')

@section('content')
<div class="text-center mb-4">
    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
         style="width:84px;height:84px;background:var(--pink-100);color:var(--pink-600);border-radius:50%;font-size:32px;animation:pulse-soft 2s infinite">
        <i class="fa-solid fa-envelope-open-text"></i>
    </div>
    <h4 class="fw-bold mb-2">Verifique seu e-mail</h4>
    <p class="text-muted">
        Enviamos um link para <strong class="text-pink">{{ auth()->user()->email }}</strong>.<br>
        Clique no link para ativar sua conta.
    </p>
</div>

<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <div class="small">
        Não recebeu? Verifique sua caixa de spam ou clique abaixo para reenviar.
    </div>
</div>

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-pink btn-lg w-100">
        <i class="fa-solid fa-paper-plane me-2"></i>Reenviar link de verificação
    </button>
</form>

<div class="text-center mt-3">
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-link text-muted small">
            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Sair da conta
        </button>
    </form>
</div>
@endsection
