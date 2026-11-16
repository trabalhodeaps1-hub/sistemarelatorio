<?php

namespace App\Notifications;

use App\Models\Ocorrencia;

class OcorrenciaReenviada extends NotificacaoBase
{
    public function __construct(public Ocorrencia $ocorrencia) {}

    protected function titulo(): string
    {
        return 'Ocorrência reenviada '.$this->ocorrencia->numero;
    }

    protected function mensagem(): string
    {
        return "{$this->ocorrencia->titulo} foi corrigida e reenviada ({$this->ocorrencia->reenvios}º reenvio).";
    }

    protected function url(): string
    {
        return route('analises.show', $this->ocorrencia, false);
    }

    protected function nivel(): string
    {
        return self::ALERTA;
    }
}
