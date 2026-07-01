<?php

namespace App\Enums;

enum PagamentoStatus: string
{
    case Pendente   = 'pendente';
    case Confirmado = 'confirmado';
    case Cancelado  = 'cancelado';
    case Estornado  = 'estornado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente   => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::Cancelado  => 'Cancelado',
            self::Estornado  => 'Estornado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente   => 'warning',
            self::Confirmado => 'success',
            self::Cancelado  => 'danger',
            self::Estornado  => 'secondary',
        };
    }
}
