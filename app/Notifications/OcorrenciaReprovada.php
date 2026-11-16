<?php

namespace App\Notifications;

use App\Models\Ocorrencia;
use App\Models\OcorrenciaAnalise;

class OcorrenciaReprovada extends NotificacaoBase
{
    public function __construct(public Ocorrencia $ocorrencia, public OcorrenciaAnalise $analise) {}

    protected function titulo(): string
    {
        return 'Ocorrência reprovada '.$this->ocorrencia->numero;
    }

    protected function mensagem(): string
    {
        return 'Motivo: '.$this->analise->motivo->descricao.'. Corrija e reenvie.';
    }

    protected function url(): string
    {
        return route('ocorrencias.show', $this->ocorrencia, false);
    }

    protected function nivel(): string
    {
        return self::ALERTA;
    }
}
