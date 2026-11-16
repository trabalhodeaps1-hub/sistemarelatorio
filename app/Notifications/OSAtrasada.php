<?php

namespace App\Notifications;

use App\Models\OrdemServico;

class OSAtrasada extends NotificacaoBase
{
    public function __construct(public OrdemServico $os) {}

    protected function titulo(): string
    {
        return 'OS atrasada '.$this->os->numero;
    }

    protected function mensagem(): string
    {
        return "Prazo vencido em {$this->os->prazo_previsto->format('d/m/Y H:i')} — {$this->os->ocorrencia->titulo}";
    }

    protected function url(): string
    {
        return route('ordens-servico.show', $this->os, false);
    }

    protected function nivel(): string
    {
        return self::CRITICO;
    }
}
