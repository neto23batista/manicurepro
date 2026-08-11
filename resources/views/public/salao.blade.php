<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $salao->nome }}</title>
    <meta name="app-env" content="{{ app()->environment() }}">
    <x-theme-vars />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta property="og:title" content="{{ $salao->nome }}">
    <meta property="og:description" content="{{ $salao->descricao ?? 'Salão de manicure e pedicure profissional' }}">
</head>
<body class="public-body">

@php
    $heroImage = null;
    if ($salao->foto_capa && file_exists(public_path('storage/' . $salao->foto_capa))) {
        $heroImage = asset('storage/' . $salao->foto_capa);
    } elseif ($salao->galeria->isNotEmpty()) {
        $heroImage = $salao->galeria->first()->foto_url;
    }

    $instagramUrl = null;
    if ($salao->instagram) {
        $instagramUrl = str_starts_with($salao->instagram, 'http')
            ? $salao->instagram
            : 'https://instagram.com/' . ltrim($salao->instagram, '@');
    }
    $facebookUrl = null;
    if ($salao->facebook) {
        $facebookUrl = str_starts_with($salao->facebook, 'http')
            ? $salao->facebook
            : 'https://facebook.com/' . ltrim($salao->facebook, '@/');
    }
@endphp

<x-skip-link />
<x-public-navbar :show-auth-buttons="true" />

<main id="mainContent" tabindex="-1">
{{-- Hero brand-first: nome do salão + CTA; full-bleed quando há imagem --}}
<section class="salao-hero {{ $heroImage ? 'salao-hero--bleed' : 'salao-hero--plain' }}" @if($heroImage) style="--hero-image: url('{{ $heroImage }}')" @endif>
    @if($heroImage)
        <div class="salao-hero-media" aria-hidden="true"></div>
        <div class="salao-hero-veil" aria-hidden="true"></div>
    @endif

    <div class="container salao-hero-content">
        <div class="salao-hero-copy">
            <p class="salao-hero-status">
                @if($salao->estaAberto())
                    <span class="salao-status-dot" aria-hidden="true"></span> Aberto agora
                @else
                    Fechado no momento
                @endif
                @if($salao->nota_media > 0)
                    <span class="salao-hero-sep" aria-hidden="true">·</span>
                    <span><i class="fas fa-star" aria-hidden="true"></i> {{ $salao->nota_media }}</span>
                @endif
            </p>

            <h1 class="salao-name">{{ $salao->nome }}</h1>

            <p class="salao-hero-lead">
                {{ $salao->descricao ? Str::limit($salao->descricao, 120) : 'Manicure e pedicure com agendamento online.' }}
            </p>

            <div class="salao-hero-cta">
                <a href="{{ route('public.agendar', $salao->slug) }}" class="btn btn-pink btn-lg salao-cta-primary">
                    Agendar horário
                </a>
                @if($salao->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $salao->whatsapp) }}"
                       class="btn btn-lg salao-cta-secondary"
                       target="_blank" rel="noopener">
                        WhatsApp
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">

        {{-- ===== Coluna Esquerda ===== --}}
        <div class="col-lg-8">

            {{-- Tabs --}}
            <ul class="nav nav-pills gap-2 mb-4 public-tabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#servicos-tab">Serviços</a></li>
                @if($salao->galeria->isNotEmpty())
                    <li class="nav-item"><a class="nav-link text-dark" data-bs-toggle="pill" href="#trabalhos-tab">Trabalhos</a></li>
                @endif
                <li class="nav-item"><a class="nav-link text-dark" data-bs-toggle="pill" href="#equipe-tab">Equipe</a></li>
                <li class="nav-item"><a class="nav-link text-dark" data-bs-toggle="pill" href="#avaliacoes-tab">Avaliações</a></li>
            </ul>

            <div class="tab-content">
                {{-- Serviços --}}
                <div class="tab-pane fade show active" id="servicos-tab">
                    @forelse($servicos as $categoriaNome => $grupoServicos)
                        <h6 class="text-muted text-uppercase fw-semibold mt-2 mb-3 public-section-label">
                            <i class="fas fa-tag text-pink me-1" aria-hidden="true"></i> {{ $categoriaNome ?: 'Outros serviços' }}
                        </h6>
                        <div class="row g-3 mb-4">
                            @foreach($grupoServicos as $servico)
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1 me-3">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="fw-bold mb-0">{{ $servico->nome }}</h6>
                                                    @if($servico->combo)
                                                        <span class="badge bg-warning text-white">COMBO</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-clock text-pink me-1" aria-hidden="true"></i> {{ $servico->duracao }} min
                                                </div>
                                                @if($servico->descricao)
                                                    <p class="text-muted small mb-0">{{ Str::limit($servico->descricao, 70) }}</p>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold fs-5 text-gradient">{{ $servico->preco_formatado }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="alert alert-light">Nenhum serviço cadastrado.</div>
                    @endforelse
                </div>

                {{-- Trabalhos / Galeria --}}
                @if($salao->galeria->isNotEmpty())
                <div class="tab-pane fade" id="trabalhos-tab">
                    <div class="row g-2 g-md-3">
                        @foreach($salao->galeria as $foto)
                            <div class="col-6 col-md-4">
                                <button type="button" class="btn p-0 border-0 w-100 galeria-thumb"
                                        data-galeria-src="{{ $foto->foto_url }}"
                                        data-galeria-titulo="{{ $foto->titulo }}"
                                        title="{{ $foto->titulo ?: 'Ampliar' }}">
                                    <div class="ratio ratio-1x1">
                                        <img src="{{ $foto->foto_url }}" alt="{{ $foto->titulo ?: 'Trabalho do salão' }}"
                                             loading="lazy" decoding="async"
                                             class="object-fit-cover rounded-3 shadow-sm w-100 h-100">
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Equipe --}}
                <div class="tab-pane fade" id="equipe-tab">
                    <div class="row g-3">
                        @foreach($salao->manicures as $manicure)
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm text-center p-4 h-100">
                                    <img src="{{ $manicure->foto_url }}" alt="{{ $manicure->nome }}" class="rounded-circle mx-auto mb-3 avatar-lg avatar-bordered-pink">
                                    <h6 class="fw-bold mb-1">{{ $manicure->nome }}</h6>
                                    @if($manicure->nota_media > 0)
                                        <div class="text-warning small mb-2"><i class="fas fa-star" aria-hidden="true"></i> {{ $manicure->nota_media }}</div>
                                    @endif
                                    @if($manicure->bio)
                                        <p class="text-muted small mb-0">{{ Str::limit($manicure->bio, 60) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Avaliações --}}
                <div class="tab-pane fade" id="avaliacoes-tab">
                    @forelse($salao->avaliacoes as $av)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex gap-1">
                                        @for($i=1;$i<=5;$i++)
                                            <i class="fas fa-star {{ $i <= $av->nota ? 'text-warning' : 'text-muted' }} small" aria-hidden="true"></i>
                                        @endfor
                                    </div>
                                    <small class="text-muted">{{ $av->created_at->diffForHumans() }}</small>
                                </div>
                                @if($av->comentario)
                                    <p class="mb-1 fst-italic">"{{ $av->comentario }}"</p>
                                @endif
                                <small class="text-muted">— {{ $av->agendamento?->nome_cliente_exibido ?? 'Cliente' }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-star fa-2x mb-2 opacity-50" aria-hidden="true"></i>
                            <p>Ainda sem avaliações.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Coluna Direita ===== --}}
        <div class="col-lg-4">
            {{-- CTA agendamento (interação) --}}
            <div class="card border-0 mb-4 cta-card">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2">Reserve seu horário</h5>
                    <p class="small mb-3 opacity-90">Agende online em segundos — sem fila, sem ligação.</p>
                    <a href="{{ route('public.agendar', $salao->slug) }}" class="btn btn-light text-pink fw-bold w-100">
                        Agendar agora
                    </a>
                </div>
            </div>

            {{-- Contato / redes --}}
            @if($salao->endereco_completo || $salao->telefone || $salao->whatsapp || $instagramUrl || $facebookUrl)
            <div class="mb-4 salao-contact-block">
                @if($salao->endereco_completo)
                    <p class="small mb-2">
                        <i class="fas fa-location-dot text-pink me-2" aria-hidden="true"></i>{{ $salao->endereco_completo }}
                    </p>
                @endif
                @if($salao->telefone)
                    <p class="small mb-2">
                        <i class="fas fa-phone text-pink me-2" aria-hidden="true"></i>{{ $salao->telefone }}
                    </p>
                @endif
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if($salao->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $salao->whatsapp) }}"
                           class="btn btn-sm btn-outline-pink" target="_blank" rel="noopener"
                           aria-label="WhatsApp">
                            <i class="fab fa-whatsapp me-1" aria-hidden="true"></i> WhatsApp
                        </a>
                    @endif
                    @if($instagramUrl)
                        <a href="{{ $instagramUrl }}" class="btn btn-sm btn-outline-pink" target="_blank" rel="noopener"
                           aria-label="Instagram">
                            <i class="fab fa-instagram me-1" aria-hidden="true"></i> Instagram
                        </a>
                    @endif
                    @if($facebookUrl)
                        <a href="{{ $facebookUrl }}" class="btn btn-sm btn-outline-pink" target="_blank" rel="noopener"
                           aria-label="Facebook">
                            <i class="fab fa-facebook-f me-1" aria-hidden="true"></i> Facebook
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Horários --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-clock text-pink me-2" aria-hidden="true"></i>Horários</h6>
                </div>
                <div class="card-body p-0">
                    @php
                        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $hoje = now()->dayOfWeek;
                    @endphp
                    <ul class="list-group list-group-flush">
                        @foreach($salao->horarios->sortBy('dia_semana') as $h)
                            <li class="list-group-item d-flex justify-content-between {{ $h->dia_semana==$hoje?'bg-pink-light fw-semibold':'' }}">
                                <span>{{ $dias[$h->dia_semana] }} {{ $h->dia_semana==$hoje?'(hoje)':'' }}</span>
                                @if(!$h->ativo)
                                    <span class="text-muted small">Fechado</span>
                                @else
                                    <span class="small">{{ substr($h->hora_abertura,0,5) }} – {{ substr($h->hora_fechamento,0,5) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Localização --}}
            @if($salao->endereco_completo)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-location-dot text-pink me-2" aria-hidden="true"></i>Localização</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-3">{{ $salao->endereco_completo }}</p>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($salao->endereco_completo) }}" target="_blank" rel="noopener" class="btn btn-outline-pink btn-sm w-100">
                        <i class="fas fa-map me-1" aria-hidden="true"></i> Ver no mapa
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<x-public-footer compact :salao="$salao" />
</main>

{{-- Lightbox da galeria (preenchido via JS delegado — CSP-safe) --}}
@if($salao->galeria->isNotEmpty())
<div class="modal fade" id="galeriaLightbox" tabindex="-1" aria-hidden="true" aria-label="Foto ampliada" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="position-relative text-center">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <img data-galeria-target="img" src="" alt="" class="img-fluid rounded-3 shadow-lg">
                <p data-galeria-target="caption" class="text-white mt-2 mb-0"></p>
            </div>
        </div>
    </div>
</div>
@endif

</body>
</html>
