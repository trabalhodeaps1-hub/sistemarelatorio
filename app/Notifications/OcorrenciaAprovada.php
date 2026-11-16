<?php

namespace App\Notifications;

use App\Enums\Criticidade;
use App\Models\OrdemServico;

/** Enviada ao técnico (aprovação da sua ocorrência) e aos gerentes (nova OS aberta). */
class OcorrenciaAprovada extends NotificacaoBase
{
    public function __construct(public OrdemServico $os, public bool $paraGerente = false) {}

    protected function titulo(): string
    {
        return $this->paraGerente
            ? 'Nova OS '.$this->os->numero
            : 'Ocorrência aprovada '.$this->os->ocorrencia->numero;
    }

    protected function mensagem(): string
    {
        $prazo = $this->os->prazo_previsto?->format('d/m/Y H:i');

        return $this->paraGerente
            ? "{$this->os->ocorrencia->titulo} — prazo {$prazo}"
            : "Sua ocorrência gerou a {$this->os->numero}.";
    }

    protected function url(): string
    {
        return $this->paraGerente
            ? route('ordens-servico.show', $this->os, false)
            : route('ocorrencias.show', $this->os->ocorrencia, false);
    }

    protected function nivel(): string
    {
        return $this->paraGerente && $this->os->criticidade === Criticidade::Critica ? self::CRITICO : self::INFO;
    }
}
