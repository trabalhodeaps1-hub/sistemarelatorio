<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notificações gravadas no banco (sino). Formato padrão do payload:
 * titulo, mensagem, url e nivel (info | alerta | critico).
 */
abstract class NotificacaoBase extends Notification
{
    public const INFO = 'info';

    public const ALERTA = 'alerta';

    public const CRITICO = 'critico';

    abstract protected function titulo(): string;

    abstract protected function mensagem(): string;

    abstract protected function url(): string;

    protected function nivel(): string
    {
        return self::INFO;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo(),
            'mensagem' => $this->mensagem(),
            'url' => $this->url(),
            'nivel' => $this->nivel(),
        ];
    }
}
