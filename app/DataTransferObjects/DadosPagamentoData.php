<?php

namespace App\DataTransferObjects;

use App\Enums\FormaPagamento;

final readonly class DadosPagamentoData
{
    public function __construct(
        public FormaPagamento $forma = FormaPagamento::Dinheiro,
        public ?float $valor = null,
        public ?string $observacao = null,
    ) {}

    public static function fromArray(array $d): self
    {
        $forma = $d['forma'] ?? null;
        return new self(
            forma:      $forma instanceof FormaPagamento
                            ? $forma
                            : (FormaPagamento::tryFrom((string) ($forma ?? '')) ?? FormaPagamento::Dinheiro),
            valor:      isset($d['valor']) ? (float) $d['valor'] : null,
            observacao: $d['observacao'] ?? null,
        );
    }
}
