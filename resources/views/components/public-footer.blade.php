@props(['compact' => false, 'salao' => null])

@php
    /** @var \App\Models\Salao|null $salao */
    $socialRaw = [
        'instagram' => config('manicure.social.instagram') ?: $salao?->instagram,
        'facebook'  => config('manicure.social.facebook') ?: $salao?->facebook,
        'tiktok'    => config('manicure.social.tiktok'),
        'whatsapp'  => config('manicure.social.whatsapp') ?: $salao?->whatsapp,
    ];

    $normalizeSocialUrl = static function (?string $value, string $rede): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return match ($rede) {
            'instagram' => 'https://instagram.com/' . ltrim($value, '@'),
            'facebook'  => 'https://facebook.com/' . ltrim($value, '@/'),
            'tiktok'    => 'https://www.tiktok.com/@' . ltrim($value, '@'),
            'whatsapp'  => 'https://wa.me/' . preg_replace('/\D/', '', $value),
            default     => null,
        };
    };

    $social = collect($socialRaw)
        ->map(fn ($value, $rede) => $normalizeSocialUrl($value, $rede))
        ->filter()
        ->all();

    $icons = [
        'instagram' => 'fa-instagram',
        'facebook'  => 'fa-facebook-f',
        'tiktok'    => 'fa-tiktok',
        'whatsapp'  => 'fa-whatsapp',
    ];
@endphp

@if($compact)
    <footer class="public-footer-compact">
        <div class="container">
            <small>&copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong> &middot; Beleza ao alcance de um toque</small>
        </div>
    </footer>
@else
    <footer class="public-footer">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-logo-sm brand-logo-gradient">F</div>
                        <span class="fw-bold text-white fs-5">{{ config('app.name') }}</span>
                    </div>
                    <p class="small text-white-50">Cuidado, capricho e beleza para as suas unhas — com agendamento online rápido e fácil.</p>
                    @if(count($social) > 0)
                        <div class="d-flex gap-2 mt-3">
                            @foreach($social as $rede => $url)
                                <a href="{{ $url }}"
                                   class="btn btn-outline-light btn-sm rounded-circle"
                                   aria-label="{{ ucfirst($rede) }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="fab {{ $icons[$rede] }}" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-white mb-3">Navegação</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="/" class="text-white-50 text-decoration-none">Início</a></li>
                        <li class="mb-2"><a href="/#servicos" class="text-white-50 text-decoration-none">Serviços</a></li>
                        @guest
                            <li class="mb-2"><a href="{{ route('login') }}" class="text-white-50 text-decoration-none">Entrar</a></li>
                            <li class="mb-2"><a href="{{ route('register') }}" class="text-white-50 text-decoration-none">Criar conta</a></li>
                        @endguest
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-white mb-3">Receba novidades</h6>
                    <p class="small text-white-50 mb-2">Tendências, dicas e promoções no seu e-mail.</p>
                    <form class="d-flex gap-2" data-newsletter>
                        <input type="email" class="form-control form-control-sm public-footer-email" placeholder="seu@email.com" aria-label="Seu e-mail">
                        <button class="btn btn-pink btn-sm" type="submit">Assinar</button>
                    </form>
                </div>
            </div>
            <hr class="border-light opacity-25">
            <div class="d-flex justify-content-between flex-wrap gap-2 small text-white-50">
                <div>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Todos os direitos reservados</div>
                <div>Feito com <i class="fas fa-heart text-pink" aria-hidden="true"></i> para o universo da beleza</div>
            </div>
        </div>
    </footer>
@endif
