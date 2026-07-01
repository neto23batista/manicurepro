<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin     = 'admin';
    case Dono      = 'dono';
    case Atendente = 'atendente';
    case Manicure  = 'manicure';
    case Cliente   = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Admin     => 'Administrador',
            self::Dono      => 'Dono de Salão',
            self::Atendente => 'Atendente',
            self::Manicure  => 'Manicure',
            self::Cliente   => 'Cliente',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin     => 'fa-user-shield',
            self::Dono      => 'fa-crown',
            self::Atendente => 'fa-headset',
            self::Manicure  => 'fa-hand-sparkles',
            self::Cliente   => 'fa-user',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin     => 'danger',
            self::Dono      => 'warning',
            self::Atendente => 'info',
            self::Manicure  => 'pink',
            self::Cliente   => 'primary',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin                  => 'admin.dashboard',
            self::Dono, self::Atendente  => 'dono.dashboard',
            self::Manicure               => 'manicure.dashboard',
            self::Cliente                => 'cliente.dashboard',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $r) => ['value' => $r->value, 'label' => $r->label()],
            self::cases()
        );
    }
}
