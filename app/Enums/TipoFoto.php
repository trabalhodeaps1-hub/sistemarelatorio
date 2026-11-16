<?php

namespace App\Enums;

enum TipoFoto: string
{
    case Antes = 'antes';
    case Depois = 'depois';

    public function label(): string
    {
        return match ($this) {
            self::Antes => 'Antes',
            self::Depois => 'Depois',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Antes => 'text-bg-secondary',
            self::Depois => 'text-bg-success',
        };
    }
}
