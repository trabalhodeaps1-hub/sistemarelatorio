<?php

namespace App\Services;

use App\Enums\Criticidade;
use App\Enums\Role;
use App\Enums\StatusOcorrencia;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Models\OcorrenciaAnalise;
use App\Models\OrdemServico;
use App\Models\User;
use App\Notifications\OcorrenciaAprovada;
use App\Notifications\OcorrenciaReprovada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AnaliseService
{
    public function __construct(private OrdemServicoService $ordens) {}

    /** Aprova a ocorrência e gera a OS com prazo pelo SLA. */
    public function aprovar(Ocorrencia $ocorrencia, User $supervisor, ?Criticidade $criticidade = null, ?string $comentario = null): OrdemServico
    {
        $os = DB::transaction(function () use ($ocorrencia, $supervisor, $criticidade, $comentario) {
            $ocorrencia = $this->bloquearPendente($ocorrencia);

            $ocorrencia->update([
                'status' => StatusOcorrencia::Aprovada,
                'criticidade' => $criticidade ?? $ocorrencia->criticidade,
            ]);

            $ocorrencia->analises()->create([
                'user_id' => $supervisor->id,
                'acao' => StatusOcorrencia::Aprovada,
                'comentario' => $comentario,
            ]);

            return $this->ordens->abrirParaOcorrencia($ocorrencia, $supervisor);
        });

        $os->load('ocorrencia.user');

        Notification::send(User::ativos()->comRole(Role::Gerente)->get(), new OcorrenciaAprovada($os, paraGerente: true));
        $this->notificarTecnico($os->ocorrencia, new OcorrenciaAprovada($os));

        return $os;
    }

    /** Reprova a ocorrência; ela volta ao técnico com o motivo. */
    public function reprovar(Ocorrencia $ocorrencia, User $supervisor, MotivoReprovacao $motivo, ?string $comentario = null): OcorrenciaAnalise
    {
        if ($motivo->exige_comentario && blank($comentario)) {
            throw ValidationException::withMessages(['comentario' => 'Este motivo exige um comentário.']);
        }

        [$ocorrencia, $analise] = DB::transaction(function () use ($ocorrencia, $supervisor, $motivo, $comentario) {
            $ocorrencia = $this->bloquearPendente($ocorrencia);

            $ocorrencia->update(['status' => StatusOcorrencia::Reprovada]);

            $analise = $ocorrencia->analises()->create([
                'user_id' => $supervisor->id,
                'acao' => StatusOcorrencia::Reprovada,
                'motivo_reprovacao_id' => $motivo->id,
                'comentario' => $comentario,
            ]);

            return [$ocorrencia, $analise];
        });

        $this->notificarTecnico($ocorrencia, new OcorrenciaReprovada($ocorrencia, $analise->load('motivo')));

        return $analise;
    }

    /** lockForUpdate evita que dois supervisores analisem a mesma ocorrência. */
    private function bloquearPendente(Ocorrencia $ocorrencia): Ocorrencia
    {
        $atual = Ocorrencia::lockForUpdate()->findOrFail($ocorrencia->id);

        if ($atual->status !== StatusOcorrencia::Pendente) {
            throw ValidationException::withMessages([
                'geral' => "A ocorrência {$atual->numero} já foi analisada (status: {$atual->status->label()}).",
            ]);
        }

        return $atual;
    }

    private function notificarTecnico(Ocorrencia $ocorrencia, $notificacao): void
    {
        $tecnico = $ocorrencia->user()->first();

        if ($tecnico && $tecnico->ativo) {
            $tecnico->notify($notificacao);
        }
    }
}
