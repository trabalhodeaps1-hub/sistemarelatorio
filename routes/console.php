<?php

use Illuminate\Support\Facades\Schedule;

// Laravel 12: agendamentos ficam aqui (não existe app/Console/Kernel.php)
Schedule::command('os:verificar-atrasos')->hourly()->withoutOverlapping();
