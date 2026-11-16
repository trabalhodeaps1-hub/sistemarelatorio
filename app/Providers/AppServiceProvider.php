<?php

namespace App\Providers;

use App\View\Composers\NotificacoesComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Carbon::setLocale(config('app.locale'));

        View::composer('partials.notificacoes-dropdown', NotificacoesComposer::class);
    }
}
