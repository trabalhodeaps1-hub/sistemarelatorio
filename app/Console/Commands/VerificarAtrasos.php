<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\OrdemServico;
use App\Models\User;
use App\Notifications\OSAtrasada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Notifica os gerentes sobre OS com prazo vencido — uma única vez por OS
 * (controlado por atraso_notificado_em; zerado se o prazo for reprogramado).
 */
class VerificarAtrasos extends Command
{
    protected $signature = 'os:verificar-atrasos';

    protected $description = 'Notifica os gerentes sobre ordens de serviço atrasadas (uma vez por OS)';

    public function handle(): int
    {
        $gerentes = User::ativos()->comRole(Role::Gerente)->get();
        $total = 0;

        OrdemServico::atrasadas()
            ->whereNull('atraso_notificado_em')
            ->with('ocorrencia')
            ->chunkById(100, function ($ordens) use ($gerentes, &$total) {
                foreach ($ordens as $os) {
                    // Marca antes de notificar: em caso de execução concorrente, não duplica
                    $marcada = OrdemServico::whereKey($os->id)
                        ->whereNull('atraso_notificado_em')
                        ->update(['atraso_notificado_em' => now()]);

                    if ($marcada) {
                        Notification::send($gerentes, new OSAtrasada($os));
                        $total++;
                    }
                }
            });

        $this->info("{$total} OS atrasada(s) notificada(s).");

        return self::SUCCESS;
    }
}
