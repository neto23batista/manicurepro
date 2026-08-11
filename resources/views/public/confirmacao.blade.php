<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação · {{ $agendamento->salao->nome }}</title>
    <meta name="app-env" content="{{ app()->environment() }}">
    <x-theme-vars :salao="$agendamento->salao" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-body">
<x-skip-link />
<x-public-navbar :show-auth-buttons="false" />

<main id="mainContent" class="container py-5" tabindex="-1">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-5">
                    @if($ok)
                        <div class="empty-state-icon mx-auto mb-3" style="background:var(--success-soft);color:var(--success);">
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                        </div>
                        <h3 class="fw-bold mb-2">Presença confirmada! 🌸</h3>
                        <p class="text-muted mb-4">
                            @if($jaConfirmado)
                                Sua presença já estava confirmada. Obrigado!
                            @else
                                Obrigado por confirmar. Já avisamos o salão.
                            @endif
                        </p>
                    @else
                        <div class="empty-state-icon mx-auto mb-3" style="background:var(--warning-soft);color:var(--warning);">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                        </div>
                        <h3 class="fw-bold mb-2">Não foi possível confirmar</h3>
                        <p class="text-muted mb-4">
                            Este agendamento não está mais aguardando confirmação
                            (status atual: {{ $agendamento->status_label }}).
                        </p>
                    @endif

                    <div class="text-start bg-pink-light rounded-3 p-3 mb-4">
                        <div class="fw-semibold">{{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}</div>
                        <div class="small text-muted">
                            {{ $agendamento->manicure->nome }} ·
                            {{ $agendamento->servicos->pluck('nome')->implode(', ') }}
                        </div>
                    </div>

                    <a href="{{ route('public.salao', $agendamento->salao->slug) }}" class="btn btn-outline-pink">
                        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i> Ir para o salão
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
