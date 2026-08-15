<?php

namespace App\Enums;

enum FormaPagamento: string
{
    case Dinheiro = 'dinheiro';
    case CartaoCredito = 'cartao_credito';
    case CartaoDebito = 'cartao_debito';
    case Pix = 'pix';
    case Transferencia = 'transferencia';
    case Voucher = 'voucher';

    public function label(): string
    {
        return match ($this) {
            self::Dinheiro      => 'Dinheiro',
            self::CartaoCredito => 'Cartão de Crédito',
            self::CartaoDebito  => 'Cartão de Débito',
            self::Pix           => 'PIX',
            self::Transferencia => 'Transferência',
            self::Voucher       => 'Voucher',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Dinheiro      => 'fa-money-bill',
            self::CartaoCredito => 'fa-credit-card',
            self::CartaoDebito  => 'fa-credit-card',
            self::Pix           => 'fa-qrcode',
            self::Transferencia => 'fa-arrow-right-arrow-left',
            self::Voucher       => 'fa-ticket',
        };
    }
}
