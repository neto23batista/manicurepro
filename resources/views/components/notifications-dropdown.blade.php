{{-- Variáveis $notificacoesRecentes e $notificacoesNaoLidasQtd vêm do NotificacoesComposer --}}
<div class="dropdown">
    <button class="btn btn-ghost position-relative"
            data-bs-toggle="dropdown"
            aria-label="Notificações ({{ $notificacoesNaoLidasQtd ?? 0 }} não lidas)">
        <i class="fas fa-bell" aria-hidden="true"></i>
        @if(($notificacoesNaoLidasQtd ?? 0) > 0)
            <span class="badge-notification" aria-hidden="true">{{ $notificacoesNaoLidasQtd }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
        <div class="dropdown-header"><strong>Notificações</strong></div>
        @forelse($notificacoesRecentes ?? [] as $notif)
            <a href="{{ $notif->url ?? '#' }}"
               class="dropdown-item notification-item {{ !$notif->lida ? 'unread' : '' }}">
                <i class="fas fa-bell text-pink me-2" aria-hidden="true"></i>
                <div>
                    <div class="fw-semibold">{{ $notif->titulo }}</div>
                    <small class="text-muted">{{ $notif->mensagem }}</small>
                </div>
            </a>
        @empty
            <div class="dropdown-item text-center text-muted py-3">
                <i class="fas fa-check-circle text-success me-1" aria-hidden="true"></i> Nenhuma notificação
            </div>
        @endforelse
    </div>
</div>
