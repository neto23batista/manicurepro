<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    /**
     * Recebe notificações da Mercado Pago (sem CSRF/sessão).
     * Responde 200 rapidamente e sincroniza o status do sinal.
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->assinaturaValida($request)) {
            return response()->json(['error' => 'assinatura inválida'], 401);
        }

        $type = $request->input('type', $request->query('topic'));

        if ($type && $type !== 'payment') {
            return response()->json(['ignored' => true]);
        }

        $paymentId = $request->input('data.id', $request->query('id'));

        if (! $paymentId) {
            return response()->json(['ok' => true]);
        }

        $agendamento = Agendamento::where('mp_payment_id', $paymentId)->first();

        if ($agendamento) {
            $this->mp->sincronizarStatus($agendamento);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Valida a assinatura do webhook (cabeçalho x-signature, HMAC-SHA256).
     * Se Mercado Pago estiver habilitado e o secret ausente, rejeita (fail-closed).
     * Fora disso, sem secret também rejeita — nunca aceitar webhooks anônimos.
     */
    private function assinaturaValida(Request $request): bool
    {
        $secret = config('manicure.pagamento.mercadopago.webhook_secret');
        if (! $secret) {
            return false;
        }

        $xSignature = (string) $request->header('x-signature', '');
        $xRequestId = (string) $request->header('x-request-id', '');
        $dataId = $request->query('data.id') ?? $request->input('data.id');

        $ts = null;
        $v1 = null;
        foreach (explode(',', $xSignature) as $parte) {
            [$chave, $valor] = array_pad(explode('=', trim($parte), 2), 2, null);
            if ($chave === 'ts') {
                $ts = $valor;
            }
            if ($chave === 'v1') {
                $v1 = $valor;
            }
        }

        if (! $ts || ! $v1) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hash, $v1);
    }
}
