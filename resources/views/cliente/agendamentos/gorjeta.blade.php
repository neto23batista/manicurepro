@extends('layouts.app')

@section('title', 'Gorjeta via Pix')
@section('page-title', 'Gorjeta via Pix')

@section('content')
@php($valor = (float) ($agendamento->mp_gorjeta_valor ?? $pix['valor'] ?? 0))
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold mb-1">Gorjeta para {{ $agendamento->manicure->nome }}</h5>
                <p class="text-muted mb-3">
                    {{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}
                </p>

                @if(empty($pix))
                    <p class="text-muted mb-4">Escolha um valor e pague via Pix. O valor vai para a comanda do atendimento.</p>
                    <form method="POST" action="{{ route('cliente.agendamentos.gorjeta', $agendamento) }}" class="text-start">
                        @csrf
                        <label class="form-label fw-semibold" for="valorGorjeta">Valor da gorjeta (R$)</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text" aria-hidden="true">R$</span>
                            <input type="number" name="valor" id="valorGorjeta" class="form-control @error('valor') is-invalid @enderror"
                                   min="1" step="0.01" max="9999" value="{{ old('valor', 10) }}" required
                                   aria-describedby="valorGorjetaHelp">
                            @error('valor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <p id="valorGorjetaHelp" class="form-text mb-3">Mínimo R$ 1,00.</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-pink">
                                <i class="fas fa-qrcode me-2" aria-hidden="true"></i> Gerar Pix
                            </button>
                            <a href="{{ route('cliente.agendamentos.show', $agendamento) }}" class="btn btn-outline-secondary">Voltar</a>
                        </div>
                    </form>
                @else
                    <div class="display-6 fw-bold text-gradient mb-3">
                        R$ {{ number_format($valor, 2, ',', '.') }}
                    </div>

                    @if(!empty($pix['qr_code_base64']))
                        <img src="data:image/png;base64,{{ $pix['qr_code_base64'] }}"
                             alt="QR Code Pix da gorjeta" class="img-fluid rounded-3 border mb-3" style="max-width:260px;">
                    @endif

                    @if(!empty($pix['qr_code']))
                        <label class="form-label fw-semibold d-block text-start" for="pixCopiaCola">Pix copia e cola</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="pixCopiaCola" value="{{ $pix['qr_code'] }}" readonly aria-label="Código Pix copia e cola">
                            <button class="btn btn-outline-pink" type="button" id="btnCopiarPix" aria-label="Copiar código Pix">
                                <i class="fas fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif

                    <div class="alert alert-info justify-content-center" id="statusBox" role="status" aria-live="polite">
                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        <span>Aguardando pagamento…</span>
                    </div>

                    <a href="{{ route('cliente.agendamentos.show', $agendamento) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i> Voltar
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@if(!empty($pix))
@push('scripts')
<script>
function copiarPix() {
    const el = document.getElementById('pixCopiaCola');
    el.select();
    navigator.clipboard?.writeText(el.value);
    window.showToast?.('Código Pix copiado!', 'success');
}
document.getElementById('btnCopiarPix')?.addEventListener('click', copiarPix);

const statusUrl = "{{ route('cliente.agendamentos.gorjeta.status', $agendamento) }}";
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

async function verificarPagamento() {
    try {
        const r = await fetch(statusUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const j = await r.json();
        if (j.pago && j.redirect) {
            const box = document.getElementById('statusBox');
            box.className = 'alert alert-success justify-content-center';
            box.innerHTML = '<i class="fas fa-circle-check" aria-hidden="true"></i><span>Gorjeta confirmada! Redirecionando…</span>';
            clearInterval(timer);
            setTimeout(() => window.location.href = j.redirect, 1200);
        }
    } catch (e) { /* mantém tentando */ }
}

const timer = setInterval(verificarPagamento, 5000);
</script>
@endpush
@endif
