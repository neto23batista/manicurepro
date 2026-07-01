@extends('layouts.app')

@section('title', 'Verificação em duas etapas')
@section('page-title', 'Verificação em duas etapas')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if($ativo)
                    <div class="alert alert-success alert-permanent">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        <div>A verificação em duas etapas está <strong>ativa</strong> na sua conta.</div>
                    </div>

                    @error('password')<div class="alert alert-danger alert-permanent">{{ $message }}</div>@enderror

                    <form method="POST" action="{{ route('2fa.disable') }}">
                        @csrf @method('DELETE')
                        <label class="form-label" for="password">Para desativar, confirme sua senha</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required>
                            <button type="submit" class="btn btn-outline-danger">Desativar</button>
                        </div>
                    </form>
                @else
                    <h6 class="fw-bold mb-2">1. Cadastre a chave no seu app autenticador</h6>
                    <p class="text-muted small mb-3">Google Authenticator, Authy, Microsoft Authenticator, etc.</p>

                    <div class="bg-pink-light rounded-3 p-3 mb-3 text-center">
                        <div class="small text-muted mb-1">Chave manual</div>
                        <code class="fs-5 fw-bold">{{ $secret }}</code>
                    </div>

                    <details class="mb-4">
                        <summary class="text-muted small">URI otpauth (avançado)</summary>
                        <code class="small d-block mt-2" style="word-break:break-all">{{ $uri }}</code>
                    </details>

                    <h6 class="fw-bold mb-2">2. Digite o código de 6 dígitos gerado</h6>
                    @error('code')<div class="alert alert-danger alert-permanent">{{ $message }}</div>@enderror

                    <form method="POST" action="{{ route('2fa.enable') }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="code" class="form-control form-control-lg text-center"
                               inputmode="numeric" maxlength="6" placeholder="000000"
                               autocomplete="one-time-code" required style="letter-spacing:.3em;">
                        <button type="submit" class="btn btn-pink btn-lg">Ativar</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
