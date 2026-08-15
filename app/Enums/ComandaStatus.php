<?php

namespace App\Enums;

enum ComandaStatus: string
{
    case Aberta = 'aberta';
    case Fechada = 'fechada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Aberta    => 'Aberta',
            self::Fechada   => 'Fechada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aberta    => 'warning',
            self::Fechada   => 'success',
            self::Cancelada => 'danger',
        };
    }
}
