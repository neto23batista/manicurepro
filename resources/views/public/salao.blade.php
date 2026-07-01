<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $salao->nome }}</title>
    <meta name="app-env" content="{{ app()->environment() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="theme-color" content="#ec4899">
    <meta property="og:title" content="{{ $salao->nome }}">
    <meta property="og:description" content="{{ $salao->descricao ?? 'Salão de manicure e pedicure profissional' }}">
</head>
<body class="public-body">

<x-public-navbar />

{{-- Hero do salão --}}
<section class="salao-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-2 text-center">
                <div class="rounded-4 bg-white d-inline-flex align-items-center justify-content-center shadow-lg salao-logo-frame">
                    <img src="{{ $salao->logo_url }}" alt="{{ $salao->nome }}" class="rounded-3 salao-logo-img">
                </div>
            </div>
            <div class="col-md-10">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @if($salao->estaAberto())
                        <span class="badge bg-success px-3 py-2">
                            <i class="fas fa-circle me-1 dot-pulse" aria-hidden="true"></i> Aberto agora
                        </span>
                    @else
                        <span class="badge bg-secondary px-3 py-2"><i class="fas fa-moon me-1" aria-hidden="true"></i> Fechado</span>
                    @endif
                    @if($salao->nota_media > 0)
                        <span class="badge bg-warning px-3 py-2">
                            <i class="fas fa-star me-1" aria-hidden="true"></i> {{ $salao->nota_media }} · {{ $salao->avaliacoes->count() }} avaliações
                        </span>
                    @endif
                    <span class="badge bg-pink px-3 py-2"><i class="fas fa-check-circle me-1" aria-hidden="true"></i> Verificado</span>
                </div>
                <h1 class="fw-bold mb-2 salao-name">{{ $salao->nome }}</h1>
                @if($salao->descricao)
                    <p class="mb-3 text-muted">{{ $salao->descricao }}</p>
                @endif
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    @if($salao->endereco_completo)
                        <span><i class="fas fa-location-dot text-pink me-1" aria-hidden="true"></i> {{ $salao->endereco_completo }}</span>
                    @endif
                    @if($salao->telefone)
                        <span><i class="fas fa-phone text-pink me-1" aria-hidden="true"></i> {{ $salao->telefone }}</span>
                    @endif
                    @if($salao->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $salao->whatsapp) }}" class="text-success text-decoration-none" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp me-1" aria-hidden="true"></i> WhatsApp
                        </a>
                    @endif
                </div>
                <div class="mt-4">
                    <a href="{{ route('public.agendar', $salao->slug) }}" class="btn btn-pink btn-lg">
                        <i class="fas fa-calendar-plus me-2" aria-hidden="true"></i> Agendar agora
                    </a>
                    @if($salao->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $salao->whatsapp) }}" class="btn btn-outline-pink btn-lg ms-2" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp me-1" aria-hidden="true"></i> Conversar
                        </a>
                    @endif
                </div>
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
                                             loading="lazy" class="object-fit-cover rounded-3 shadow-sm w-100 h-100">
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
            {{-- Card destaque agendamento --}}
            <div class="card border-0 mb-4 cta-card">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-hand-sparkles fs-1 mb-3 opacity-75" aria-hidden="true"></i>
                    <h5 class="fw-bold">Pronto para o glow?</h5>
                    <p class="small mb-3 opacity-90">Agende online em segundos, sem complicação.</p>
                    <a href="{{ route('public.agendar', $salao->slug) }}" class="btn btn-light text-pink fw-bold w-100">
                        <i class="fas fa-calendar-plus me-1" aria-hidden="true"></i> Reservar horário
                    </a>
                </div>
            </div>

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

<x-public-footer compact />

{{-- Lightbox da galeria (preenchido via JS delegado — CSP-safe) --}}
@if($salao->galeria->isNotEmpty())
<div class="modal fade" id="galeriaLightbox" tabindex="-1" aria-hidden="true">
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
