<?php

namespace App\Enums;

enum StatusOcorrencia: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovada => 'Aprovada',
            self::Reprovada => 'Reprovada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendente => 'text-bg-warning',
            self::Aprovada => 'text-bg-success',
            self::Reprovada => 'text-bg-danger',
            self::Cancelada => 'text-bg-secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
