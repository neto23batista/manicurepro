<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\WebhookEvent;
use App\Services\MercadoPagoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    /**
     * Recebe notificações da Mercado Pago (sem CSRF/sessão).
     * Responde 200 rapidamente, deduplica por payment_id e sincroniza o status.
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

        $paymentId = (string) $paymentId;
        $payloadHash = hash('sha256', $request->getContent() ?: json_encode($request->all()));

        if (! $this->reservarProcessamento($paymentId, $payloadHash)) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $agendamento = Agendamento::where('mp_payment_id', $paymentId)->first();

        if ($agendamento) {
            $this->mp->sincronizarStatus($agendamento);
        }

        WebhookEvent::where('provider', 'mercadopago')
            ->where('event_id', $paymentId)
            ->update(['processed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Insere o evento; se (provider, event_id) já existir, é duplicata.
     */
    private function reservarProcessamento(string $paymentId, string $payloadHash): bool
    {
        try {
            WebhookEvent::create([
                'provider'     => 'mercadopago',
                'event_id'     => $paymentId,
                'payload_hash' => $payloadHash,
                'processed_at' => null,
            ]);

            return true;
        } catch (QueryException $e) {
            // Unique (provider, event_id) — evento já visto.
            if ($this->isUniqueViolation($e)) {
                return false;
            }

            throw $e;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        // SQLSTATE 23000 + MySQL 1062 / SQLite 19 / Postgres unique_violation
        return $sqlState === '23000'
            || $driverCode === 1062
            || $driverCode === 19
            || str_contains(strtolower($e->getMessage()), 'unique');
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
