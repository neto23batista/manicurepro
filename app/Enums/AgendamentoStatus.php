<?php

namespace App\Enums;

enum AgendamentoStatus: string
{
    case Aguardando    = 'aguardando';
    case Confirmado    = 'confirmado';
    case EmAndamento   = 'em_andamento';
    case Concluido     = 'concluido';
    case Cancelado     = 'cancelado';
    case NaoCompareceu = 'nao_compareceu';

    public function label(): string
    {
        return match ($this) {
            self::Aguardando    => 'Aguardando',
            self::Confirmado    => 'Confirmado',
            self::EmAndamento   => 'Em Andamento',
            self::Concluido     => 'Concluído',
            self::Cancelado     => 'Cancelado',
            self::NaoCompareceu => 'Não Compareceu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aguardando    => 'warning',
            self::Confirmado    => 'info',
            self::EmAndamento   => 'primary',
            self::Concluido     => 'success',
            self::Cancelado     => 'danger',
            self::NaoCompareceu => 'secondary',
        };
    }

    public function podeSerCancelado(): bool
    {
        return in_array($this, [self::Aguardando, self::Confirmado], true);
    }

    public function podeSerReagendado(): bool
    {
        return in_array($this, [self::Aguardando, self::Confirmado], true);
    }

    public function podeSerIniciado(): bool
    {
        return in_array($this, [self::Aguardando, self::Confirmado], true);
    }

    public function podeSerFinalizado(): bool
    {
        return $this === self::EmAndamento;
    }

    public function isFinalizado(): bool
    {
        return in_array($this, [self::Concluido, self::Cancelado, self::NaoCompareceu], true);
    }

    public static function ativos(): array
    {
        return [self::Aguardando, self::Confirmado, self::EmAndamento];
    }

    public static function ativosValues(): array
    {
        return array_map(fn($s) => $s->value, self::ativos());
    }
}
