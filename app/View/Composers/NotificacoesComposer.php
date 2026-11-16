<?php

namespace App\View\Composers;

use Illuminate\View\View;

/** Alimenta o sino da topbar: contador de não lidas e as últimas 10. */
class NotificacoesComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $view->with([
            'notificacoesNaoLidas' => $user->unreadNotifications()->count(),
            'ultimasNotificacoes' => $user->notifications()->limit(10)->get(),
        ]);
    }
}
