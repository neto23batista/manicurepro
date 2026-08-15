@extends('layouts.app')

@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')
<div class="row g-4 justify-content-center">
    {{-- Card lateral com foto e info --}}
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body p-4">
                <div class="position-relative d-inline-block mb-3">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                         class="rounded-circle" style="width:120px;height:120px;object-fit:cover;border:4px solid var(--pink-200)">
                    @if($user->avatar)
                    <form method="POST" action="{{ route('perfil.avatar.destroy') }}"
                          class="position-absolute" style="bottom:0;right:0;"
                          data-confirm="Remover foto?" data-confirm-message="Sua foto voltará para o avatar padrão.">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm rounded-circle" style="width:32px;height:32px;padding:0" title="Remover foto">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>
                    @endif
                </div>
                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <span class="badge bg-pink mb-2">{{ ucfirst($user->role) }}</span>
                <p class="text-muted small mb-0">{{ $user->email }}</p>
                <hr>
                <ul class="list-unstyled small text-start mb-0">
                    <li class="mb-2"><i class="fas fa-calendar text-pink me-2"></i>Membro desde {{ $user->created_at->format('m/Y') }}</li>
                    @if($user->salao)
                        <li class="mb-2"><i class="fas fa-store text-pink me-2"></i>{{ $user->salao->nome }}</li>
                    @endif
                    @if($user->phone)
                        <li class="mb-2"><i class="fas fa-phone text-pink me-2"></i>{{ $user->phone }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Formulário de edição --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-pen text-pink me-2"></i>Editar perfil</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Avatar upload --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Foto de perfil</label>
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $user->avatar_url }}" class="rounded-circle" style="width:60px;height:60px;object-fit:cover" id="avatarPreview">
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" id="avatarInput" accept="image/*"
                                       class="form-control @error('avatar') is-invalid @enderror">
                                <small class="text-muted">JPG, PNG ou WebP — máximo 2 MB</small>
                                @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome completo *</label>
                            <input type="text" name="name" required
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail *</label>
                            <input type="email" name="email" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="tel" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="(00) 00000-0000">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="fas fa-lock text-pink me-2"></i>Alterar senha</h6>
                    <p class="text-muted small">Deixe em branco para manter a senha atual.</p>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Senha atual</label>
                            <input type="password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   autocomplete="current-password">
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nova senha</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmar nova senha</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="fas fa-calendar text-pink me-2"></i>Calendário</h6>
                    <p class="text-muted small mb-3">Conecte Google ou Outlook para sincronizar seus agendamentos (opcional).</p>
                    <div class="d-flex flex-wrap gap-2">
                        @php
                            $googleConn = $calendarConnections['google'] ?? null;
                            $outlookConn = $calendarConnections['outlook'] ?? null;
                        @endphp

                        @if($calendarGoogleOk ?? false)
                            @if($googleConn)
                                <form method="POST" action="{{ route('calendar.oauth.disconnect', 'google') }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        <i class="fab fa-google me-1"></i>Desconectar Google
                                        @if($googleConn->email)<span class="text-muted">({{ $googleConn->email }})</span>@endif
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('calendar.oauth.redirect', 'google') }}" class="btn btn-outline-pink btn-sm">
                                    <i class="fab fa-google me-1"></i>Conectar Google
                                </a>
                            @endif
                        @endif

                        @if($calendarOutlookOk ?? false)
                            @if($outlookConn)
                                <form method="POST" action="{{ route('calendar.oauth.disconnect', 'outlook') }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        <i class="fab fa-microsoft me-1"></i>Desconectar Outlook
                                        @if($outlookConn->email)<span class="text-muted">({{ $outlookConn->email }})</span>@endif
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('calendar.oauth.redirect', 'outlook') }}" class="btn btn-outline-pink btn-sm">
                                    <i class="fab fa-microsoft me-1"></i>Conectar Outlook
                                </a>
                            @endif
                        @endif

                        @if(! ($calendarGoogleOk ?? false) && ! ($calendarOutlookOk ?? false))
                            <small class="text-muted">Nenhum provedor de calendário configurado no servidor.</small>
                        @endif
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i>Salvar alterações
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- LGPD: privacidade e dados --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-shield me-2 text-pink"></i>Privacidade e dados (LGPD)</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <div class="fw-semibold">Baixar meus dados</div>
                        <small class="text-muted">Exporta tudo o que guardamos sobre você em um arquivo JSON.</small>
                    </div>
                    <a href="{{ route('perfil.exportar') }}" class="btn btn-outline-pink">
                        <i class="fas fa-download me-2"></i> Exportar
                    </a>
                </div>

                @if(!auth()->user()->isDono() && !auth()->user()->isSuperAdmin())
                    <hr>
                    <div class="fw-semibold text-danger">Excluir minha conta</div>
                    <small class="text-muted d-block mb-2">Ação permanente. Seu histórico é anonimizado e você perde o acesso.</small>
                    @error('password')<div class="alert alert-danger alert-permanent">{{ $message }}</div>@enderror
                    <form method="POST" action="{{ route('perfil.conta.destroy') }}"
                          data-confirm="Excluir minha conta?" data-confirm-ok="Excluir conta">
                        @csrf @method('DELETE')
                        <div class="input-group" style="max-width:420px">
                            <input type="password" name="password" class="form-control" placeholder="Confirme sua senha" required>
                            <button type="submit" class="btn btn-outline-danger">Excluir conta</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Preview do avatar antes de enviar
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = ev => document.getElementById('avatarPreview').src = ev.target.result;
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
