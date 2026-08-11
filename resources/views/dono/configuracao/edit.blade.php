@extends('layouts.app')

@section('title', 'Configurações')
@section('page-title', 'Configurações do salão')

@section('content')
@php
    $corPrimaria = old('cor_primaria', $config->cor_primaria ?: config('manicure.tema.cor_primaria', '#e91e8c'));
    $tab = request('tab', session('tab', 'identidade'));
    $tabs = [
        'identidade'    => 'Identidade',
        'operacao'      => 'Operação',
        'aparencia'     => 'Aparência',
        'fidelidade'    => 'Fidelidade',
        'notificacoes'  => 'Notificações',
        'permissoes'    => 'Permissões',
    ];
    if (! array_key_exists($tab, $tabs)) {
        $tab = 'identidade';
    }
@endphp
<ul class="nav nav-pills gap-2 mb-4 flex-wrap" style="background:white;padding:6px;border-radius:14px;box-shadow:var(--shadow-sm);display:inline-flex" role="tablist">
    @foreach($tabs as $id => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $id ? 'active' : 'text-dark' }}"
               data-bs-toggle="pill" href="#{{ $id }}" role="tab"
               aria-selected="{{ $tab === $id ? 'true' : 'false' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    {{-- Identidade --}}
    <div class="tab-pane fade {{ $tab === 'identidade' ? 'show active' : '' }}" id="identidade" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-store text-pink me-2"></i>Dados do salão</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.dados') }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Logo do salão</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $salao->logo_url }}" id="logoPreview"
                                     class="rounded-3 border" style="width:72px;height:72px;object-fit:cover" alt="Logo">
                                <div class="flex-grow-1">
                                    <input type="file" name="logo" id="logoInput" accept="image/*"
                                           class="form-control form-control-sm @error('logo') is-invalid @enderror">
                                    <small class="text-muted">PNG/JPG/WebP · até 3 MB</small>
                                    @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if($salao->logo)
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 mt-1"
                                                data-submit-form="formRemoveLogo">
                                            <i class="fas fa-trash"></i> Remover logo
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Foto de capa</label>
                            <div class="position-relative" style="aspect-ratio:3/1;background:var(--ink-100);border-radius:var(--radius-sm);overflow:hidden">
                                @if($salao->foto_capa)
                                    <img src="{{ asset('storage/' . $salao->foto_capa) }}" id="capaPreview"
                                         style="width:100%;height:100%;object-fit:cover" alt="Capa">
                                @else
                                    <div id="capaPreview" class="d-flex align-items-center justify-content-center h-100 text-muted">
                                        <span><i class="fas fa-image fa-2x mb-2 d-block"></i> Sem capa</span>
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <input type="file" name="foto_capa" id="capaInput" accept="image/*"
                                       class="form-control form-control-sm @error('foto_capa') is-invalid @enderror">
                                @if($salao->foto_capa)
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            data-submit-form="formRemoveCapa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                            <small class="text-muted">PNG/JPG/WebP · proporção 3:1 · até 5 MB</small>
                            @error('foto_capa') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label fw-semibold">Nome *</label>
                            <input type="text" name="nome" required class="form-control" value="{{ old('nome', $salao->nome) }}"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Telefone</label>
                            <input type="tel" name="telefone" class="form-control" value="{{ old('telefone', $salao->telefone) }}"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">E-mail</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $salao->email) }}"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">WhatsApp</label>
                            <input type="tel" name="whatsapp" class="form-control" value="{{ old('whatsapp', $salao->whatsapp) }}"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Endereço</label>
                            <input type="text" name="endereco" class="form-control" value="{{ old('endereco', $salao->endereco) }}"></div>
                        <div class="col-md-2"><label class="form-label fw-semibold">Número</label>
                            <input type="text" name="numero" class="form-control" value="{{ old('numero', $salao->numero) }}"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Bairro</label>
                            <input type="text" name="bairro" class="form-control" value="{{ old('bairro', $salao->bairro) }}"></div>
                        <div class="col-md-5"><label class="form-label fw-semibold">Cidade</label>
                            <input type="text" name="cidade" class="form-control" value="{{ old('cidade', $salao->cidade) }}"></div>
                        <div class="col-md-2"><label class="form-label fw-semibold">UF</label>
                            <input type="text" name="estado" maxlength="2" class="form-control" value="{{ old('estado', $salao->estado) }}"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">CEP</label>
                            <input type="text" name="cep" class="form-control" value="{{ old('cep', $salao->cep) }}"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Instagram</label>
                            <div class="input-group"><span class="input-group-text">@</span>
                                <input type="text" name="instagram" class="form-control" value="{{ old('instagram', $salao->instagram) }}"></div></div>
                        <div class="col-12"><label class="form-label fw-semibold">Descrição</label>
                            <textarea name="descricao" rows="3" maxlength="1000" class="form-control">{{ old('descricao', $salao->descricao) }}</textarea></div>
                    </div>
                    <button type="submit" class="btn btn-pink mt-4"><i class="fas fa-save me-1"></i>Salvar dados</button>
                </form>

                <form action="{{ route('dono.config.logo.destroy') }}" method="POST" id="formRemoveLogo" class="d-none"
                      data-confirm="Remover logo?" data-confirm-message="A logo voltará para o padrão.">
                    @csrf @method('DELETE')
                </form>
                <form action="{{ route('dono.config.capa.destroy') }}" method="POST" id="formRemoveCapa" class="d-none"
                      data-confirm="Remover capa?" data-confirm-message="A imagem de capa será removida.">
                    @csrf @method('DELETE')
                </form>
            </div>
        </div>
    </div>

    {{-- Operação: horários + regras de agendamento --}}
    <div class="tab-pane fade {{ $tab === 'operacao' ? 'show active' : '' }}" id="operacao" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-clock text-pink me-2"></i>Horários de funcionamento</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.horarios') }}" method="POST">
                    @csrf @method('PUT')
                    @php $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado']; @endphp
                    @foreach($dias as $i => $nome)
                        @php $h = $horarios[$i] ?? null; @endphp
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="horarios[{{ $i }}][ativo]" value="0">
                                    <input class="form-check-input" type="checkbox" name="horarios[{{ $i }}][ativo]" value="1"
                                           id="d{{ $i }}" {{ $h?->ativo ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="d{{ $i }}">{{ $nome }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="time" name="horarios[{{ $i }}][hora_abertura]" class="form-control"
                                       value="{{ $h ? substr($h->hora_abertura, 0, 5) : '09:00' }}">
                            </div>
                            <div class="col-md-1 text-center text-muted">até</div>
                            <div class="col-md-4">
                                <input type="time" name="horarios[{{ $i }}][hora_fechamento]" class="form-control"
                                       value="{{ $h ? substr($h->hora_fechamento, 0, 5) : '18:00' }}">
                            </div>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-pink mt-3"><i class="fas fa-save me-1"></i>Salvar horários</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-calendar text-pink me-2"></i>Regras de agendamento</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.config') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="permitir_agendamento_online" value="0">
                                <input class="form-check-input" type="checkbox" name="permitir_agendamento_online" value="1" id="agOnline"
                                       {{ $config->permitir_agendamento_online ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="agOnline">Permitir agendamento online (público)</label>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Intervalo entre slots (min)</label>
                            <input type="number" name="intervalo_agendamento" value="{{ $config->intervalo_agendamento }}" min="5" max="240" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Antecedência mínima (dias)</label>
                            <input type="number" name="antecedencia_minima" value="{{ $config->antecedencia_minima }}" min="0" max="30" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Antecedência máxima (dias)</label>
                            <input type="number" name="antecedencia_maxima" value="{{ $config->antecedencia_maxima }}" min="1" max="365" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Prazo p/ cancelamento (horas)</label>
                            <input type="number" name="cancelamento_prazo" value="{{ $config->cancelamento_prazo }}" min="0" max="72" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="limiteAlertaNoShow">Limite de faltas p/ alerta</label>
                            <input type="number" name="limite_alerta_no_show" id="limiteAlertaNoShow"
                                   value="{{ old('limite_alerta_no_show', $config->limite_alerta_no_show ?? config('manicure.no_show.limite_alerta', 2)) }}"
                                   min="1" max="20" class="form-control @error('limite_alerta_no_show') is-invalid @enderror" required>
                            @error('limite_alerta_no_show') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <input type="hidden" name="cor_primaria" value="{{ $corPrimaria }}">
                    <input type="hidden" name="fidelidade_ativo" value="{{ $config->fidelidade_ativo ? 1 : 0 }}">
                    <input type="hidden" name="pontos_por_real" value="{{ $config->pontos_por_real }}">
                    <input type="hidden" name="pontos_para_desconto" value="{{ $config->pontos_para_desconto }}">
                    <input type="hidden" name="valor_desconto_pontos" value="{{ $config->valor_desconto_pontos }}">
                    <input type="hidden" name="notificar_email" value="{{ $config->notificar_email ? 1 : 0 }}">
                    <input type="hidden" name="notificar_whatsapp" value="{{ $config->notificar_whatsapp ? 1 : 0 }}">
                    <input type="hidden" name="lembrete_horas" value="{{ $config->lembrete_horas }}">
                    <button type="submit" class="btn btn-pink mt-4"><i class="fas fa-save me-1"></i>Salvar operação</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Aparência --}}
    <div class="tab-pane fade {{ $tab === 'aparencia' ? 'show active' : '' }}" id="aparencia" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-palette text-pink me-2"></i>Aparência do salão</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.config') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-4 align-items-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="corPrimariaInput">Cor primária</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" id="corPrimariaPicker" value="{{ $corPrimaria }}"
                                       class="form-control form-control-color" style="width:56px;height:40px;padding:2px"
                                       aria-label="Selecionar cor primária">
                                <input type="text" name="cor_primaria" id="corPrimariaInput" value="{{ $corPrimaria }}"
                                       maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required
                                       class="form-control @error('cor_primaria') is-invalid @enderror"
                                       style="max-width:140px;font-family:ui-monospace,monospace"
                                       placeholder="#e91e8c" autocomplete="off">
                                @error('cor_primaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pré-visualização</label>
                            <div id="corPrimariaPreview" class="rounded-3 border p-3"
                                 style="background:{{ $corPrimaria }}15;border-color:{{ $corPrimaria }}!important">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span id="corPrimariaSwatch" class="rounded-circle border"
                                          style="width:40px;height:40px;background:{{ $corPrimaria }};display:inline-block"></span>
                                    <div>
                                        <div class="fw-semibold" id="corPrimariaLabel" style="color:{{ $corPrimaria }}">{{ strtoupper($corPrimaria) }}</div>
                                        <small class="text-muted">Cor de destaque</small>
                                    </div>
                                </div>
                                <button type="button" id="corPrimariaBtnPreview" class="btn btn-sm text-white"
                                        style="background:{{ $corPrimaria }};border-color:{{ $corPrimaria }}" disabled>
                                    Botão de exemplo
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="permitir_agendamento_online" value="{{ $config->permitir_agendamento_online ? 1 : 0 }}">
                    <input type="hidden" name="intervalo_agendamento" value="{{ $config->intervalo_agendamento }}">
                    <input type="hidden" name="antecedencia_minima" value="{{ $config->antecedencia_minima }}">
                    <input type="hidden" name="antecedencia_maxima" value="{{ $config->antecedencia_maxima }}">
                    <input type="hidden" name="cancelamento_prazo" value="{{ $config->cancelamento_prazo }}">
                    <input type="hidden" name="fidelidade_ativo" value="{{ $config->fidelidade_ativo ? 1 : 0 }}">
                    <input type="hidden" name="pontos_por_real" value="{{ $config->pontos_por_real }}">
                    <input type="hidden" name="pontos_para_desconto" value="{{ $config->pontos_para_desconto }}">
                    <input type="hidden" name="valor_desconto_pontos" value="{{ $config->valor_desconto_pontos }}">
                    <input type="hidden" name="notificar_email" value="{{ $config->notificar_email ? 1 : 0 }}">
                    <input type="hidden" name="notificar_whatsapp" value="{{ $config->notificar_whatsapp ? 1 : 0 }}">
                    <input type="hidden" name="lembrete_horas" value="{{ $config->lembrete_horas }}">
                    <input type="hidden" name="limite_alerta_no_show" value="{{ $config->limite_alerta_no_show ?? config('manicure.no_show.limite_alerta', 2) }}">
                    <button type="submit" class="btn btn-pink mt-4"><i class="fas fa-save me-1"></i>Salvar aparência</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Fidelidade --}}
    <div class="tab-pane fade {{ $tab === 'fidelidade' ? 'show active' : '' }}" id="fidelidade" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-star text-pink me-2"></i>Programa de fidelidade</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.config') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="fidelidade_ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="fidelidade_ativo" value="1" id="fidAtivo"
                               {{ $config->fidelidade_ativo ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="fidAtivo">Ativar programa de pontos</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label fw-semibold">Pontos por R$ 1,00</label>
                            <input type="number" name="pontos_por_real" min="0" value="{{ $config->pontos_por_real }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Pontos para desconto</label>
                            <input type="number" name="pontos_para_desconto" min="0" value="{{ $config->pontos_para_desconto }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Valor desconto (R$)</label>
                            <input type="number" step="0.01" name="valor_desconto_pontos" min="0" value="{{ $config->valor_desconto_pontos }}" class="form-control"></div>
                    </div>
                    <input type="hidden" name="cor_primaria" value="{{ $corPrimaria }}">
                    <input type="hidden" name="permitir_agendamento_online" value="{{ $config->permitir_agendamento_online ? 1 : 0 }}">
                    <input type="hidden" name="intervalo_agendamento" value="{{ $config->intervalo_agendamento }}">
                    <input type="hidden" name="antecedencia_minima" value="{{ $config->antecedencia_minima }}">
                    <input type="hidden" name="antecedencia_maxima" value="{{ $config->antecedencia_maxima }}">
                    <input type="hidden" name="cancelamento_prazo" value="{{ $config->cancelamento_prazo }}">
                    <input type="hidden" name="notificar_email" value="{{ $config->notificar_email ? 1 : 0 }}">
                    <input type="hidden" name="notificar_whatsapp" value="{{ $config->notificar_whatsapp ? 1 : 0 }}">
                    <input type="hidden" name="lembrete_horas" value="{{ $config->lembrete_horas }}">
                    <input type="hidden" name="limite_alerta_no_show" value="{{ $config->limite_alerta_no_show ?? config('manicure.no_show.limite_alerta', 2) }}">
                    <button type="submit" class="btn btn-pink mt-4"><i class="fas fa-save me-1"></i>Salvar fidelidade</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Notificações --}}
    <div class="tab-pane fade {{ $tab === 'notificacoes' ? 'show active' : '' }}" id="notificacoes" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-bell text-pink me-2"></i>Notificações</h5></div>
            <div class="card-body p-4">
                <form action="{{ route('dono.config.config') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="notificar_email" value="0">
                        <input class="form-check-input" type="checkbox" name="notificar_email" value="1" id="notifEmail"
                               {{ $config->notificar_email ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="notifEmail">Enviar confirmações por e-mail</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="notificar_whatsapp" value="0">
                        <input class="form-check-input" type="checkbox" name="notificar_whatsapp" value="1" id="notifWa"
                               {{ $config->notificar_whatsapp ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="notifWa">Notificações via WhatsApp (em breve)</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enviar lembrete X horas antes</label>
                        <input type="number" name="lembrete_horas" min="1" max="168" value="{{ $config->lembrete_horas }}" class="form-control" style="max-width:200px">
                    </div>
                    <input type="hidden" name="cor_primaria" value="{{ $corPrimaria }}">
                    <input type="hidden" name="permitir_agendamento_online" value="{{ $config->permitir_agendamento_online ? 1 : 0 }}">
                    <input type="hidden" name="intervalo_agendamento" value="{{ $config->intervalo_agendamento }}">
                    <input type="hidden" name="antecedencia_minima" value="{{ $config->antecedencia_minima }}">
                    <input type="hidden" name="antecedencia_maxima" value="{{ $config->antecedencia_maxima }}">
                    <input type="hidden" name="cancelamento_prazo" value="{{ $config->cancelamento_prazo }}">
                    <input type="hidden" name="fidelidade_ativo" value="{{ $config->fidelidade_ativo ? 1 : 0 }}">
                    <input type="hidden" name="pontos_por_real" value="{{ $config->pontos_por_real }}">
                    <input type="hidden" name="pontos_para_desconto" value="{{ $config->pontos_para_desconto }}">
                    <input type="hidden" name="valor_desconto_pontos" value="{{ $config->valor_desconto_pontos }}">
                    <input type="hidden" name="limite_alerta_no_show" value="{{ $config->limite_alerta_no_show ?? config('manicure.no_show.limite_alerta', 2) }}">
                    <button type="submit" class="btn btn-pink mt-3"><i class="fas fa-save me-1"></i>Salvar notificações</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Permissões extras --}}
    <div class="tab-pane fade {{ $tab === 'permissoes' ? 'show active' : '' }}" id="permissoes" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-user-shield text-pink me-2"></i>Permissões extras por role</h5></div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    Defaults das 5 roles <strong>não mudam</strong>. Marque apenas grants extras (ex.: liberar financeiro para atendente).
                    Sem Spatie — JSON leve em cima das roles atuais.
                </p>
                <form action="{{ route('dono.config.permissoes') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-4">
                        @foreach($roles as $role)
                            @php
                                $grants = $rolePermissions[$role->value]['grant'] ?? [];
                                // Dono/admin já têm tudo via hierarquia — grants só fazem sentido para roles menores.
                                $editavel = ! in_array($role->value, ['admin', 'dono'], true);
                            @endphp
                            <div class="col-lg-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="fw-semibold mb-2">
                                        <i class="fas {{ $role->icon() }} me-1"></i>{{ $role->label() }}
                                        @unless($editavel)
                                            <span class="badge text-bg-light text-muted ms-1">defaults</span>
                                        @endunless
                                    </div>
                                    @if($editavel)
                                        @foreach($permissionCatalog as $key => $label)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox"
                                                       name="roles[{{ $role->value }}][grant][]"
                                                       value="{{ $key }}"
                                                       id="perm_{{ $role->value }}_{{ str_replace('.', '_', $key) }}"
                                                       {{ in_array($key, $grants, true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm_{{ $role->value }}_{{ str_replace('.', '_', $key) }}">
                                                    {{ $label }}
                                                    <code class="small text-muted">{{ $key }}</code>
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="small text-muted mb-0">Acesso completo ao painel correspondente. Grants extras não se aplicam.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-pink mt-4"><i class="fas fa-save me-1"></i>Salvar permissões</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('logoInput')?.addEventListener('change', function(e) {
    const f = e.target.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = ev => document.getElementById('logoPreview').src = ev.target.result;
        r.readAsDataURL(f);
    }
});

document.getElementById('capaInput')?.addEventListener('change', function(e) {
    const f = e.target.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = ev => {
            const prev = document.getElementById('capaPreview');
            if (prev.tagName === 'IMG') {
                prev.src = ev.target.result;
            } else {
                prev.outerHTML = `<img src="${ev.target.result}" id="capaPreview" alt="Capa" style="width:100%;height:100%;object-fit:cover">`;
            }
        };
        r.readAsDataURL(f);
    }
});

(function () {
    const picker = document.getElementById('corPrimariaPicker');
    const input = document.getElementById('corPrimariaInput');
    const swatch = document.getElementById('corPrimariaSwatch');
    const label = document.getElementById('corPrimariaLabel');
    const btn = document.getElementById('corPrimariaBtnPreview');
    const box = document.getElementById('corPrimariaPreview');
    if (!picker || !input) return;

    const hexOk = (v) => /^#[0-9A-Fa-f]{6}$/.test(v);

    function applyPreview(color) {
        if (!hexOk(color)) return;
        swatch && (swatch.style.background = color);
        label && (label.textContent = color.toUpperCase(), label.style.color = color);
        if (btn) {
            btn.style.background = color;
            btn.style.borderColor = color;
        }
        if (box) {
            box.style.background = color + '15';
            box.style.borderColor = color;
        }
    }

    picker.addEventListener('input', function () {
        input.value = this.value;
        applyPreview(this.value);
    });

    input.addEventListener('input', function () {
        let v = this.value.trim();
        if (v && !v.startsWith('#')) v = '#' + v;
        this.value = v;
        if (hexOk(v)) {
            picker.value = v;
            applyPreview(v);
        }
    });
})();

// Sync ?tab= with pill clicks
document.querySelectorAll('[data-bs-toggle="pill"]').forEach((el) => {
    el.addEventListener('shown.bs.tab', (e) => {
        const id = (e.target.getAttribute('href') || '').replace('#', '');
        if (!id) return;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        window.history.replaceState({}, '', url);
    });
});
</script>
@endpush
@endsection
