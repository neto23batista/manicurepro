@php($user = auth()->user())

<div class="dropdown">
    <button class="btn btn-ghost d-flex align-items-center gap-2"
            data-bs-toggle="dropdown"
            aria-label="Menu do usuário">
        <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" width="32" height="32" class="rounded-circle">
        <span class="d-none d-md-inline">{{ $user->name }}</span>
        <i class="fas fa-chevron-down small" aria-hidden="true"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        <div class="dropdown-item-text px-3 py-2">
            <div class="fw-semibold">{{ $user->name }}</div>
            <small class="text-muted">{{ $user->email }}</small>
        </div>
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item text-danger">
                <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i> Sair
            </button>
        </form>
    </div>
</div>
