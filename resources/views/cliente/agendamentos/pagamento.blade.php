@extends('layouts.app')

@section('title', 'Pagar agendamento')
@section('page-title', 'Pagar agendamento')

@section('content')
@php($valor = (float) ($agendamento->mp_total_valor ?? $pix['valor'] ?? 0))
@php($ehRestante = $agendamento->sinalPago())
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold mb-1">
                    {{ $ehRestante ? 'Valor restante' : 'Pagamento do agendamento' }}
                </h5>
                <p class="text-muted mb-3">
                    {{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }} ·
                    {{ $agendamento->manicure->nome }}
                </p>

                <div class="display-6 fw-bold text-gradient mb-1">
                    R$ {{ number_format($valor, 2, ',', '.') }}
                </div>
                <p class="text-muted small mb-4">
                    @if($ehRestante)
                        Sinal já pago: R$ {{ number_format((float) $agendamento->sinal_valor, 2, ',', '.') }}.
                        Pague o restante via Pix.
                    @else
                        Pague o valor total via Pix.
                    @endif
                </p>

                @if(!empty($pix['qr_code_base64']))
                    <img src="data:image/png;base64,{{ $pix['qr_code_base64'] }}"
                         alt="QR Code Pix" class="img-fluid rounded-3 border mb-3" style="max-width:260px;">
                @endif

                @if(!empty($pix['qr_code']))
                    <label class="form-label fw-semibold d-block text-start">Pix copia e cola</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="pixCopiaCola" value="{{ $pix['qr_code'] }}" readonly>
                        <button class="btn btn-outline-pink" type="button" id="btnCopiarPix">
                            <i class="fas fa-copy" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif

                <div class="alert alert-info justify-content-center" id="statusBox">
                    <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                    <span>Aguardando pagamento…</span>
                </div>

                <a href="{{ route('cliente.agendamentos.show', $agendamento) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2" aria-hidden="true"></i> Voltar
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copiarPix() {
    const el = document.getElementById('pixCopiaCola');
    el.select();
    navigator.clipboard?.writeText(el.value);
    window.showToast?.('Código Pix copiado!', 'success');
}
document.getElementById('btnCopiarPix')?.addEventListener('click', copiarPix);

const statusUrl = "{{ route('cliente.agendamentos.pagamento.status', $agendamento) }}";
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
            box.innerHTML = '<i class="fas fa-circle-check"></i><span>Pagamento confirmado! Redirecionando…</span>';
            clearInterval(timer);
            setTimeout(() => window.location.href = j.redirect, 1200);
        }
    } catch (e) { /* mantém tentando */ }
}

const timer = setInterval(verificarPagamento, 5000);
</script>
@endpush
