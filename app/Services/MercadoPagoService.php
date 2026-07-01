<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoService
{
    private const API = 'https://api.mercadopago.com';

    public function habilitado(): bool
    {
        return (bool) config('manicure.pagamento.mercadopago.enabled')
            && config('manicure.pagamento.mercadopago.access_token');
    }

    public function sinalHabilitado(): bool
    {
        return $this->habilitado() && (bool) config('manicure.pagamento.sinal.habilitado');
    }

    public function calcularSinal(float $valorTotal): float
    {
        if ($valorTotal <= 0) {
            return 0.0;
        }

        $tipo  = config('manicure.pagamento.sinal.tipo', 'percentual');
        $valor = (float) config('manicure.pagamento.sinal.valor', 30);

        return $tipo === 'fixo'
            ? round(min($valor, $valorTotal), 2)
            : round($valorTotal * $valor / 100, 2);
    }

    /**
     * Cria uma cobrança Pix para o sinal do agendamento e persiste a referência.
     *
     * @return array{payment_id:?int,qr_code:?string,qr_code_base64:?string,ticket_url:?string,valor:float}
     */
    public function criarPixSinal(Agendamento $agendamento): array
    {
        $valorLiquido = (float) ($agendamento->valor_total - $agendamento->valor_desconto);
        $valorSinal = $this->calcularSinal($valorLiquido);

        $email = $agendamento->cliente?->email
            ?? $agendamento->user?->email
            ?? 'cliente@exemplo.com';

        $token = config('manicure.pagamento.mercadopago.access_token');

        $response = Http::withToken($token)
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->acceptJson()
            ->post(self::API . '/v1/payments', [
                'transaction_amount' => $valorSinal,
                'description'        => "Sinal — agendamento #{$agendamento->id}",
                'payment_method_id'  => 'pix',
                'external_reference' => (string) $agendamento->id,
                'notification_url'   => route('webhooks.mercadopago'),
                'payer'              => ['email' => $email],
            ]);

        $data = $response->json() ?? [];

        $agendamento->update([
            'mp_payment_id' => $data['id'] ?? null,
            'sinal_status'  => 'pendente',
            'sinal_valor'   => $valorSinal,
        ]);

        $poi = $data['point_of_interaction']['transaction_data'] ?? [];

        return [
            'payment_id'     => $data['id'] ?? null,
            'qr_code'        => $poi['qr_code'] ?? null,
            'qr_code_base64' => $poi['qr_code_base64'] ?? null,
            'ticket_url'     => $poi['ticket_url'] ?? null,
            'valor'          => $valorSinal,
        ];
    }

    /**
     * Consulta o pagamento na Mercado Pago, atualiza o status do sinal e
     * retorna também os dados do Pix (para reexibir o QR na tela).
     * Confirma o agendamento automaticamente quando o sinal é aprovado.
     *
     * @return array{status:?string,qr_code:?string,qr_code_base64:?string,ticket_url:?string}
     */
    public function consultarPix(Agendamento $agendamento): array
    {
        $vazio = ['status' => $agendamento->sinal_status, 'qr_code' => null, 'qr_code_base64' => null, 'ticket_url' => null];

        if (!$agendamento->mp_payment_id) {
            return $vazio;
        }

        $token = config('manicure.pagamento.mercadopago.access_token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(self::API . '/v1/payments/' . $agendamento->mp_payment_id);

        if ($response->failed()) {
            return $vazio;
        }

        $novo = $this->mapearStatus($response->json('status'));
        $agendamento->update(['sinal_status' => $novo]);

        if ($novo === 'pago' && $agendamento->statusEnum() === AgendamentoStatus::Aguardando) {
            $agendamento->update([
                'status'        => AgendamentoStatus::Confirmado->value,
                'confirmado_em' => now(),
            ]);
        }

        $poi = $response->json('point_of_interaction.transaction_data') ?? [];

        return [
            'status'         => $novo,
            'qr_code'        => $poi['qr_code'] ?? null,
            'qr_code_base64' => $poi['qr_code_base64'] ?? null,
            'ticket_url'     => $poi['ticket_url'] ?? null,
        ];
    }

    /**
     * Atualiza o status do sinal (usado pelo webhook). Retorna o status mapeado.
     */
    public function sincronizarStatus(Agendamento $agendamento): ?string
    {
        if (!$agendamento->mp_payment_id) {
            return null;
        }

        return $this->consultarPix($agendamento)['status'];
    }

    private function mapearStatus(?string $statusMp): string
    {
        return match ($statusMp) {
            'approved'                   => 'pago',
            'rejected'                   => 'rejeitado',
            'cancelled'                  => 'cancelado',
            'refunded', 'charged_back'   => 'estornado',
            default                      => 'pendente',
        };
    }
}
