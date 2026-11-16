<?php

namespace App\Enums;

enum Criticidade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function label(): string
    {
        return match ($this) {
            self::Baixa => 'Baixa',
            self::Media => 'Média',
            self::Alta => 'Alta',
            self::Critica => 'Crítica',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Baixa => 'text-bg-secondary',
            self::Media => 'text-bg-info',
            self::Alta => 'text-bg-warning',
            self::Critica => 'text-bg-danger',
        };
    }

    /** Peso para ordenação (maior = mais crítico). */
    public function peso(): int
    {
        return match ($this) {
            self::Baixa => 1,
            self::Media => 2,
            self::Alta => 3,
            self::Critica => 4,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
