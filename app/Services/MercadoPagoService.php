<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Models\Agendamento;
use App\Models\Pagamento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoPagoService
{
    private const API = 'https://api.mercadopago.com';

    public function __construct(private ComandaService $comandaService) {}

    public function habilitado(): bool
    {
        return (bool) config('manicure.pagamento.mercadopago.enabled')
            && config('manicure.pagamento.mercadopago.access_token');
    }

    public function sinalHabilitado(): bool
    {
        return $this->habilitado() && (bool) config('manicure.pagamento.sinal.habilitado');
    }

    public function totalHabilitado(): bool
    {
        return $this->habilitado() && (bool) config('manicure.pagamento.total.habilitado');
    }

    public function valorLiquido(Agendamento $agendamento): float
    {
        return max(0, round(
            (float) $agendamento->valor_total - (float) $agendamento->valor_desconto,
            2,
        ));
    }

    public function calcularSinal(float $valorTotal): float
    {
        if ($valorTotal <= 0) {
            return 0.0;
        }

        $tipo = config('manicure.pagamento.sinal.tipo', 'percentual');
        $valor = (float) config('manicure.pagamento.sinal.valor', 30);

        return $tipo === 'fixo'
            ? round(min($valor, $valorTotal), 2)
            : round($valorTotal * $valor / 100, 2);
    }

    /**
     * Valor ainda cobrável via Pix total: líquido menos sinal já pago.
     * Se o Pix total já estiver pago, retorna 0.
     */
    public function calcularValorTotal(Agendamento $agendamento): float
    {
        if ($agendamento->pagamentoTotalPago()) {
            return 0.0;
        }

        $liquido = $this->valorLiquido($agendamento);
        $sinalPago = $agendamento->sinalPago()
            ? (float) $agendamento->sinal_valor
            : 0.0;

        return max(0, round($liquido - $sinalPago, 2));
    }

    /**
     * Cria uma cobrança Pix para o sinal do agendamento e persiste a referência.
     *
     * @return array{payment_id:?int,qr_code:?string,qr_code_base64:?string,ticket_url:?string,valor:float,status:?string}
     */
    public function criarPixSinal(Agendamento $agendamento): array
    {
        $valorSinal = $this->calcularSinal($this->valorLiquido($agendamento));

        return $this->criarCobrancaPix(
            $agendamento,
            $valorSinal,
            'sinal',
            "Sinal — agendamento #{$agendamento->id}",
        );
    }

    /**
     * Cria cobrança Pix do valor total (ou restante, se o sinal já foi pago).
     *
     * @return array{payment_id:?int,qr_code:?string,qr_code_base64:?string,ticket_url:?string,valor:float,status:?string}
     */
    public function criarPixTotal(Agendamento $agendamento): array
    {
        $valor = $this->calcularValorTotal($agendamento);

        return $this->criarCobrancaPix(
            $agendamento,
            $valor,
            'total',
            "Pagamento — agendamento #{$agendamento->id}",
        );
    }

    /**
     * @return array{payment_id:?int,qr_code:?string,qr_code_base64:?string,ticket_url:?string,valor:float,status:?string}
     */
    private function criarCobrancaPix(
        Agendamento $agendamento,
        float $valor,
        string $tipo,
        string $descricao
    ): array {
        $email = ($agendamento->cliente !== null ? $agendamento->cliente->email : null)
            ?? ($agendamento->user !== null ? $agendamento->user->email : null)
            ?? 'cliente@exemplo.com';

        $token = config('manicure.pagamento.mercadopago.access_token');

        $response = Http::withToken($token)
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->acceptJson()
            ->post(self::API.'/v1/payments', [
                'transaction_amount' => $valor,
                'description'        => $descricao,
                'payment_method_id'  => 'pix',
                'external_reference' => (string) $agendamento->id,
                'notification_url'   => route('webhooks.mercadopago'),
                'payer'              => ['email' => $email],
            ]);

        $data = $response->json() ?? [];
        $paymentId = isset($data['id']) ? (string) $data['id'] : null;

        $attrs = [
            'mp_payment_id'    => $paymentId,
            'mp_cobranca_tipo' => $tipo,
        ];

        if ($tipo === 'sinal') {
            $attrs['sinal_status'] = 'pendente';
            $attrs['sinal_valor'] = $valor;
        } else {
            $attrs['mp_total_status'] = 'pendente';
            $attrs['mp_total_valor'] = $valor;
        }

        $agendamento->update($attrs);

        $poi = $data['point_of_interaction']['transaction_data'] ?? [];

        return [
            'payment_id'     => $data['id'] ?? null,
            'qr_code'        => $poi['qr_code'] ?? null,
            'qr_code_base64' => $poi['qr_code_base64'] ?? null,
            'ticket_url'     => $poi['ticket_url'] ?? null,
            'valor'          => $valor,
            'status'         => 'pendente',
        ];
    }

    /**
     * Consulta o pagamento na Mercado Pago, atualiza o status da cobrança ativa
     * e retorna também os dados do Pix (para reexibir o QR na tela).
     * Confirma o agendamento automaticamente quando o sinal/total é aprovado.
     *
     * @return array{status:?string,qr_code:?string,qr_code_base64:?string,ticket_url:?string}
     */
    public function consultarPix(Agendamento $agendamento): array
    {
        $tipo = $agendamento->mp_cobranca_tipo ?: 'sinal';
        $statusAtual = $tipo === 'total'
            ? $agendamento->mp_total_status
            : $agendamento->sinal_status;

        $vazio = ['status' => $statusAtual, 'qr_code' => null, 'qr_code_base64' => null, 'ticket_url' => null];

        if (! $agendamento->mp_payment_id) {
            return $vazio;
        }

        $token = config('manicure.pagamento.mercadopago.access_token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(self::API.'/v1/payments/'.$agendamento->mp_payment_id);

        if ($response->failed()) {
            return $vazio;
        }

        $novo = $this->mapearStatus($response->json('status'));
        $this->aplicarStatus($agendamento, $tipo, $novo);
        $agendamento->refresh();

        $statusEfetivo = $tipo === 'total'
            ? $agendamento->mp_total_status
            : $agendamento->sinal_status;

        if ($statusEfetivo === 'pago' && $agendamento->statusEnum() === AgendamentoStatus::Aguardando) {
            $agendamento->update([
                'status'        => AgendamentoStatus::Confirmado->value,
                'confirmado_em' => now(),
            ]);
        }

        if ($statusEfetivo === 'pago') {
            $this->registrarPagamentoOnline($agendamento->fresh(), $tipo);
        }

        $poi = $response->json('point_of_interaction.transaction_data') ?? [];

        return [
            'status'         => $statusEfetivo,
            'qr_code'        => $poi['qr_code'] ?? null,
            'qr_code_base64' => $poi['qr_code_base64'] ?? null,
            'ticket_url'     => $poi['ticket_url'] ?? null,
        ];
    }

    /**
     * Atualiza o status da cobrança (usado pelo webhook). Retorna o status mapeado.
     */
    public function sincronizarStatus(Agendamento $agendamento): ?string
    {
        if (! $agendamento->mp_payment_id) {
            return null;
        }

        return $this->consultarPix($agendamento)['status'];
    }

    /**
     * Best-effort: cancela cobrança pendente ou estorna pagamento aprovado na MP
     * quando o agendamento é cancelado. Falhas são apenas logadas.
     *
     * @return array{ok:bool,acao:?string,status:?string}
     */
    public function cancelarOuEstornar(Agendamento $agendamento): array
    {
        if (! $agendamento->mp_payment_id || ! $this->habilitado()) {
            return ['ok' => true, 'acao' => null, 'status' => null];
        }

        $token = config('manicure.pagamento.mercadopago.access_token');
        $paymentId = $agendamento->mp_payment_id;
        $tipo = $agendamento->mp_cobranca_tipo ?: 'sinal';

        try {
            $consulta = Http::withToken($token)
                ->acceptJson()
                ->get(self::API.'/v1/payments/'.$paymentId);

            if ($consulta->failed()) {
                Log::warning('MP cancelarOuEstornar: falha ao consultar pagamento', [
                    'agendamento_id' => $agendamento->id,
                    'payment_id'     => $paymentId,
                    'status'         => $consulta->status(),
                ]);

                return ['ok' => false, 'acao' => null, 'status' => null];
            }

            $statusMp = $consulta->json('status');
            $mapeado = $this->mapearStatus($statusMp);

            if (in_array($statusMp, ['pending', 'in_process', 'authorized'], true)) {
                $resp = Http::withToken($token)
                    ->acceptJson()
                    ->put(self::API.'/v1/payments/'.$paymentId, [
                        'status' => 'cancelled',
                    ]);

                if ($resp->successful()) {
                    $this->aplicarStatus($agendamento, $tipo, 'cancelado');
                    $this->marcarPagamentoLocal($paymentId, PagamentoStatus::Cancelado);

                    return ['ok' => true, 'acao' => 'cancelado', 'status' => 'cancelado'];
                }

                Log::warning('MP cancelarOuEstornar: falha ao cancelar', [
                    'agendamento_id' => $agendamento->id,
                    'payment_id'     => $paymentId,
                    'body'           => $resp->json(),
                ]);

                return ['ok' => false, 'acao' => 'cancelado', 'status' => $mapeado];
            }

            if ($statusMp === 'approved') {
                $resp = Http::withToken($token)
                    ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
                    ->acceptJson()
                    ->post(self::API.'/v1/payments/'.$paymentId.'/refunds');

                if ($resp->successful()) {
                    $this->aplicarStatus($agendamento, $tipo, 'estornado');
                    $this->marcarPagamentoLocal($paymentId, PagamentoStatus::Estornado);

                    return ['ok' => true, 'acao' => 'estornado', 'status' => 'estornado'];
                }

                Log::warning('MP cancelarOuEstornar: falha ao estornar', [
                    'agendamento_id' => $agendamento->id,
                    'payment_id'     => $paymentId,
                    'body'           => $resp->json(),
                ]);

                return ['ok' => false, 'acao' => 'estornado', 'status' => $mapeado];
            }

            // Já cancelado/estornado/rejeitado — só espelha o status localmente.
            $this->aplicarStatus($agendamento, $tipo, $mapeado);

            return ['ok' => true, 'acao' => null, 'status' => $mapeado];
        } catch (\Throwable $e) {
            Log::warning('MP cancelarOuEstornar: exceção', [
                'agendamento_id' => $agendamento->id,
                'payment_id'     => $paymentId,
                'erro'           => $e->getMessage(),
            ]);

            return ['ok' => false, 'acao' => null, 'status' => null];
        }
    }

    private function aplicarStatus(Agendamento $agendamento, string $tipo, string $status): void
    {
        $campo = $tipo === 'total' ? 'mp_total_status' : 'sinal_status';
        $atual = $agendamento->{$campo};

        // Não regredir pagamento aprovado → pendente (webhook/consulta atrasada).
        if ($atual === 'pago' && $status === 'pendente') {
            return;
        }

        $agendamento->update([$campo => $status]);
    }

    private function registrarPagamentoOnline(Agendamento $agendamento, string $tipo): void
    {
        $paymentId = $agendamento->mp_payment_id;
        if (! $paymentId) {
            return;
        }

        if (Pagamento::where('referencia', (string) $paymentId)->exists()) {
            return;
        }

        $valor = $tipo === 'total'
            ? (float) $agendamento->mp_total_valor
            : (float) $agendamento->sinal_valor;

        if ($valor <= 0) {
            return;
        }

        $comanda = $this->comandaService->obterOuCriar($agendamento);

        Pagamento::create([
            'comanda_id'     => $comanda->id,
            'agendamento_id' => $agendamento->id,
            'salao_id'       => $agendamento->salao_id,
            'forma'          => FormaPagamento::Pix->value,
            'valor'          => $valor,
            'status'         => PagamentoStatus::Confirmado->value,
            'referencia'     => (string) $paymentId,
            'observacoes'    => $tipo === 'total'
                ? 'Pix online (valor total/restante)'
                : 'Pix online (sinal)',
        ]);
    }

    private function marcarPagamentoLocal(string $paymentId, PagamentoStatus $status): void
    {
        Pagamento::where('referencia', $paymentId)
            ->where('status', PagamentoStatus::Confirmado->value)
            ->update(['status' => $status->value]);
    }

    private function mapearStatus(?string $statusMp): string
    {
        return match ($statusMp) {
            'approved'                 => 'pago',
            'rejected'                 => 'rejeitado',
            'cancelled'                => 'cancelado',
            'refunded', 'charged_back' => 'estornado',
            default                    => 'pendente',
        };
    }
}
