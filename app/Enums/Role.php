<?php

namespace App\Enums;

enum Role: string
{
    case Master = 'master';
    case Tecnico = 'tecnico';
    case Supervisor = 'supervisor';
    case Gerente = 'gerente';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Master',
            self::Tecnico => 'Técnico',
            self::Supervisor => 'Supervisor',
            self::Gerente => 'Gerente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Master => 'text-bg-dark',
            self::Tecnico => 'text-bg-info',
            self::Supervisor => 'text-bg-primary',
            self::Gerente => 'text-bg-success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
