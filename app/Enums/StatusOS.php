<?php

namespace App\Enums;

enum StatusOS: string
{
    case Aberta = 'aberta';
    case Programada = 'programada';
    case EmExecucao = 'em_execucao';
    case Pausada = 'pausada';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::Programada => 'Programada',
            self::EmExecucao => 'Em execução',
            self::Pausada => 'Pausada',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Aberta => 'text-bg-info',
            self::Programada => 'text-bg-primary',
            self::EmExecucao => 'text-bg-warning',
            self::Pausada => 'text-bg-secondary',
            self::Concluida => 'text-bg-success',
            self::Cancelada => 'text-bg-dark',
        };
    }

    /** Rótulo da ação que leva a este status. */
    public function acao(): string
    {
        return match ($this) {
            self::Aberta => 'Reabrir',
            self::Programada => 'Programar',
            self::EmExecucao => 'Iniciar execução',
            self::Pausada => 'Pausar',
            self::Concluida => 'Concluir',
            self::Cancelada => 'Cancelar',
        };
    }

    /** @return array<int, self> */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Aberta => [self::Programada, self::Cancelada],
            self::Programada => [self::EmExecucao, self::Pausada, self::Cancelada],
            self::EmExecucao => [self::Pausada, self::Concluida],
            self::Pausada => [self::EmExecucao, self::Cancelada],
            self::Concluida, self::Cancelada => [],
        };
    }

    public function podeTransicionarPara(self $novo): bool
    {
        return in_array($novo, $this->transicoesPermitidas(), true);
    }

    public function finalizada(): bool
    {
        return in_array($this, [self::Concluida, self::Cancelada], true);
    }

    /** @return array<int, self> */
    public static function emAndamento(): array
    {
        return [self::Aberta, self::Programada, self::EmExecucao, self::Pausada];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
