<?php

namespace App\Services\Fiscal;

use App\Contracts\NotaFiscalProvider;

/**
 * Stub local — payload marcado stub=true / sefaz=false.
 * NÃO emite na SEFAZ e não chama webservice externo.
 */
class StubNotaFiscalProvider implements NotaFiscalProvider
{
    /**
     * @param  array<string, mixed>  $dados
     * @return array{provider_id: ?string, status: string, payload: array, numero: ?string, chave: ?string}
     */
    public function criarRascunho(array $dados): array
    {
        return [
            'provider_id' => null,
            'status'      => 'rascunho',
            'numero'      => null,
            'chave'       => null,
            'payload'     => [
                'stub'      => true,
                'sefaz'     => false,
                'aviso'     => 'Rascunho local — NÃO emitir SEFAZ. Módulo stub sem integração fiscal.',
                'total'     => round((float) ($dados['total'] ?? 0), 2),
                'cliente'   => $dados['cliente'] ?? null,
                'criado_em' => now()->toIso8601String(),
            ],
        ];
    }
}
