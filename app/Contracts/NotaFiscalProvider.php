<?php

namespace App\Contracts;

/**
 * Provedor de rascunhos fiscais / NF-e.
 *
 * Implementações NÃO devem declarar autorização SEFAZ.
 * O retorno é sempre um rascunho (local ou remoto) sem chave fiscal autorizada.
 */
interface NotaFiscalProvider
{
    /**
     * Cria um rascunho fiscal a partir dos dados do atendimento.
     *
     * @param  array<string, mixed>  $dados
     * @return array{provider_id: ?string, status: string, payload: array, numero: ?string, chave: ?string}
     */
    public function criarRascunho(array $dados): array;
}
