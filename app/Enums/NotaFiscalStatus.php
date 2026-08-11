<?php

namespace App\Enums;

enum NotaFiscalStatus: string
{
    case Rascunho = 'rascunho';
    case Emitida = 'emitida';
    case Erro = 'erro';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho (stub)',
            self::Emitida  => 'Emitida',
            self::Erro     => 'Erro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Rascunho => 'secondary',
            self::Emitida  => 'success',
            self::Erro     => 'danger',
        };
    }
}
