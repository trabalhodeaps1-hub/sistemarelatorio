<?php

namespace App\Notifications;

use App\Models\OrdemServico;

class OSConcluida extends NotificacaoBase
{
    public function __construct(public OrdemServico $os) {}

    protected function titulo(): string
    {
        return 'OS concluída '.$this->os->numero;
    }

    protected function mensagem(): string
    {
        return "O problema \"{$this->os->ocorrencia->titulo}\" foi resolvido.";
    }

    protected function url(): string
    {
        return route('ocorrencias.show', $this->os->ocorrencia, false);
    }
}
