<?php

namespace App\Services;

use App\Enums\Criticidade;
use App\Enums\Role;
use App\Enums\StatusOcorrencia;
use App\Enums\StatusOS;
use App\Models\Ocorrencia;
use App\Models\OcorrenciaAnalise;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function paraUsuario(User $user): array
    {
        return match ($user->role) {
            Role::Tecnico => $this->tecnico($user),
            Role::Supervisor => $this->supervisor($user) + ['graficos' => $this->graficos()],
            Role::Gerente => $this->gerente() + ['graficos' => $this->graficos()],
            Role::Master => $this->master() + ['graficos' => $this->graficos()],
        };
    }

    private function tecnico(User $user): array
    {
        $porStatus = $user->ocorrencias()->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');

        return [
            'cards' => [
                ['Pendentes de análise', $porStatus[StatusOcorrencia::Pendente->value] ?? 0, 'hourglass-split', 'warning', route('ocorrencias.index', ['status' => 'pendente'])],
                ['Reprovadas para corrigir', $porStatus[StatusOcorrencia::Reprovada->value] ?? 0, 'pencil-square', 'danger', route('ocorrencias.index', ['status' => 'reprovada'])],
                ['Aprovadas', $porStatus[StatusOcorrencia::Aprovada->value] ?? 0, 'check-circle', 'success', route('ocorrencias.index', ['status' => 'aprovada'])],
            ],
            'reprovadas' => $user->ocorrencias()->status(StatusOcorrencia::Reprovada)->with('ultimaAnalise.motivo')->latest()->limit(5)->get(),
            'recentes' => $user->ocorrencias()->with(['area', 'ordemServico'])->latest()->limit(5)->get(),
        ];
    }

    private function supervisor(User $user): array
    {
        return [
            'cards' => [
                ['Pendentes', Ocorrencia::pendentes()->count(), 'inbox', 'warning', route('analises.index')],
                ['Críticas pendentes', Ocorrencia::pendentes()->where('criticidade', Criticidade::Critica->value)->count(), 'exclamation-octagon', 'danger', route('analises.index', ['criticidade' => 'critica'])],
                ['Analisadas hoje (por você)', OcorrenciaAnalise::where('user_id', $user->id)->whereDate('created_at', today())->count(), 'clipboard-check', 'success', null],
            ],
            'fila' => Ocorrencia::pendentes()->with(['area', 'user'])->ordemDeFila()->limit(5)->get(),
        ];
    }

    private function gerente(): array
    {
        return [
            'cards' => [
                ['Abertas', OrdemServico::where('status', StatusOS::Aberta->value)->count(), 'folder2-open', 'info', route('ordens-servico.index', ['status' => 'aberta'])],
                ['Em execução', OrdemServico::where('status', StatusOS::EmExecucao->value)->count(), 'tools', 'primary', route('ordens-servico.index', ['status' => 'em_execucao'])],
                ['Atrasadas', OrdemServico::atrasadas()->count(), 'alarm', 'danger', route('ordens-servico.index', ['situacao' => 'atrasadas'])],
                ['Vencem em 24h', OrdemServico::vencendoEm24h()->count(), 'hourglass-split', 'warning', route('ordens-servico.index', ['situacao' => 'vencendo'])],
                ['Concluídas no mês', OrdemServico::where('status', StatusOS::Concluida->value)->where('concluida_em', '>=', now()->startOfMonth())->count(), 'check2-circle', 'success', route('ordens-servico.index', ['status' => 'concluida'])],
            ],
            'atrasadas' => OrdemServico::atrasadas()->with(['ocorrencia.area', 'equipe'])->orderBy('prazo_previsto')->limit(5)->get(),
        ];
    }

    private function master(): array
    {
        return [
            'cards' => [
                ['Usuários ativos', User::ativos()->count(), 'people', 'primary', route('usuarios.index', ['situacao' => 'ativos'])],
                ['Ocorrências pendentes', Ocorrencia::pendentes()->count(), 'inbox', 'warning', route('analises.index')],
                ['OS em andamento', OrdemServico::emAndamento()->count(), 'tools', 'info', route('ordens-servico.index', ['situacao' => 'andamento'])],
                ['OS atrasadas', OrdemServico::atrasadas()->count(), 'alarm', 'danger', route('ordens-servico.index', ['situacao' => 'atrasadas'])],
                ['Concluídas no mês', OrdemServico::where('status', StatusOS::Concluida->value)->where('concluida_em', '>=', now()->startOfMonth())->count(), 'check2-circle', 'success', null],
            ],
            'atrasadas' => OrdemServico::atrasadas()->with(['ocorrencia.area', 'equipe'])->orderBy('prazo_previsto')->limit(5)->get(),
        ];
    }

    /** Ocorrências por mês (12 meses) e top 5 equipamentos. */
    public function graficos(): array
    {
        $inicio = now()->startOfMonth()->subMonths(11);

        $porMes = Ocorrencia::query()
            ->where('created_at', '>=', $inicio)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $meses = collect(range(0, 11))->map(function ($i) use ($inicio, $porMes) {
            $mes = $inicio->copy()->addMonths($i);

            return [
                'rotulo' => ucfirst($mes->translatedFormat('M/y')),
                'total' => (int) ($porMes[$mes->format('Y-m')] ?? 0),
            ];
        });

        $topEquipamentos = DB::table('ocorrencias')
            ->join('equipamentos', 'equipamentos.id', '=', 'ocorrencias.equipamento_id')
            ->whereNull('ocorrencias.deleted_at')
            ->where('ocorrencias.created_at', '>=', $inicio)
            ->groupBy('equipamentos.id', 'equipamentos.tag', 'equipamentos.nome')
            ->orderByDesc('total')
            ->limit(5)
            ->get(['equipamentos.tag', 'equipamentos.nome', DB::raw('count(*) as total')]);

        return [
            'meses' => $meses,
            'equipamentos' => $topEquipamentos,
            'periodo' => $inicio->format('m/Y').' a '.Carbon::now()->format('m/Y'),
        ];
    }
}
