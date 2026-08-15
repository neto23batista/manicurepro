<?php

namespace App\Services\Fiscal;

use App\Contracts\NotaFiscalProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente HTTP estilo FocusNFe/eNotas.
 *
 * Em falha ou configuração incompleta, cai no stub local com nota de erro no payload.
 * NUNCA marca sefaz=true nem afirma autorização SEFAZ.
 */
class HttpNotaFiscalProvider implements NotaFiscalProvider
{
    public function __construct(private StubNotaFiscalProvider $stub) {}

    /**
     * @param  array<string, mixed>  $dados
     * @return array{provider_id: ?string, status: string, payload: array, numero: ?string, chave: ?string}
     */
    public function criarRascunho(array $dados): array
    {
        $baseUrl = rtrim((string) config('manicure.fiscal.base_url', ''), '/');
        $token = (string) config('manicure.fiscal.token', '');
        $endpoint = (string) config('manicure.fiscal.draft_endpoint', '/v2/nfse');
        $timeout = (int) config('manicure.fiscal.timeout', 15);

        if ($baseUrl === '' || $token === '') {
            return $this->fallback($dados, 'Configuração fiscal HTTP incompleta (base_url/token). Usando stub local.');
        }

        try {
            $url = $baseUrl.(str_starts_with($endpoint, '/') ? $endpoint : '/'.$endpoint);

            $response = Http::withToken($token)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'rascunho' => true,
                    'total'    => round((float) ($dados['total'] ?? 0), 2),
                    'cliente'  => $dados['cliente'] ?? null,
                    'meta'     => $dados,
                ]);

            if (! $response->successful()) {
                return $this->fallback(
                    $dados,
                    'Provedor fiscal retornou HTTP '.$response->status().'. Usando stub local.',
                );
            }

            $body = $response->json() ?? [];

            return [
                'provider_id' => isset($body['id']) ? (string) $body['id'] : (isset($body['ref']) ? (string) $body['ref'] : null),
                'status'      => 'rascunho',
                'numero'      => null,
                'chave'       => null,
                'payload'     => [
                    'stub'      => false,
                    'sefaz'     => false,
                    'aviso'     => 'Rascunho enviado ao provedor HTTP — NÃO autorizado na SEFAZ por este sistema.',
                    'total'     => round((float) ($dados['total'] ?? 0), 2),
                    'cliente'   => $dados['cliente'] ?? null,
                    'criado_em' => now()->toIso8601String(),
                    'provider'  => $body,
                ],
            ];
        } catch (Throwable $e) {
            Log::warning('Fiscal HTTP falhou; usando stub.', ['erro' => $e->getMessage()]);

            return $this->fallback($dados, 'Falha na chamada ao provedor fiscal: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array{provider_id: ?string, status: string, payload: array, numero: ?string, chave: ?string}
     */
    private function fallback(array $dados, string $erro): array
    {
        $resultado = $this->stub->criarRascunho($dados);
        $resultado['payload']['erro'] = $erro;
        $resultado['payload']['aviso'] = 'Rascunho local (fallback) — NÃO emitir SEFAZ. '.$erro;

        return $resultado;
    }
}
