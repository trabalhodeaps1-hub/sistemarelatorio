<?php

namespace App\Notifications;

use App\Enums\Criticidade;
use App\Models\Ocorrencia;

class NovaOcorrencia extends NotificacaoBase
{
    public function __construct(public Ocorrencia $ocorrencia) {}

    protected function titulo(): string
    {
        return 'Nova ocorrência '.$this->ocorrencia->numero;
    }

    protected function mensagem(): string
    {
        return "{$this->ocorrencia->titulo} ({$this->ocorrencia->criticidade->label()}) — {$this->ocorrencia->area->nome}";
    }

    protected function url(): string
    {
        return route('analises.show', $this->ocorrencia, false);
    }

    protected function nivel(): string
    {
        return match ($this->ocorrencia->criticidade) {
            Criticidade::Critica => self::CRITICO,
            Criticidade::Alta => self::ALERTA,
            default => self::INFO,
        };
    }
}
