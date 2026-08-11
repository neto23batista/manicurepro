<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento confirmado · {{ $salao->nome }}</title>
    <meta name="app-env" content="{{ app()->environment() }}">
    <x-theme-vars />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-body">
<x-public-navbar :back-url="route('public.salao', $salao->slug)" :show-auth-buttons="false" />

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-5">
                    <div class="empty-state-icon mx-auto mb-3" style="background:var(--success-soft);color:var(--success);">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Agendamento recebido!</h3>
                    <p class="text-muted mb-4">
                        Guardamos seu horário. Você pode confirmar a presença pelo link abaixo
                        @if($agendamento->cliente?->email)
                            — também enviamos os detalhes para <strong>{{ $agendamento->cliente->email }}</strong>
                        @endif.
                    </p>

                    <div class="text-start bg-pink-light rounded-3 p-3 mb-4">
                        <div class="fw-semibold mb-1">{{ $agendamento->nome_cliente }}</div>
                        <div class="fw-semibold">{{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}</div>
                        <div class="small text-muted">
                            {{ $agendamento->manicure->nome }} ·
                            {{ $agendamento->servicos->pluck('nome')->implode(', ') }}
                        </div>
                        @if($agendamento->telefone_cliente)
                            <div class="small text-muted mt-1">
                                <i class="fas fa-phone me-1" aria-hidden="true"></i>{{ $agendamento->telefone_cliente }}
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ $linkConfirmacao }}" class="btn btn-pink">
                            <i class="fas fa-circle-check me-2" aria-hidden="true"></i> Confirmar presença
                        </a>
                        <a href="{{ route('public.salao', $salao->slug) }}" class="btn btn-outline-pink">
                            Voltar ao salão
                        </a>
                        <a href="{{ route('register') }}?salao={{ $salao->id }}" class="btn btn-link text-muted small">
                            Criar conta para gerenciar seus agendamentos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
