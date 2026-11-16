<?php

namespace App\Services;

use App\Enums\Criticidade;
use App\Enums\Role;
use App\Enums\StatusOcorrencia;
use App\Enums\TipoFoto;
use App\Models\Ocorrencia;
use App\Models\User;
use App\Notifications\NovaOcorrencia;
use App\Notifications\OcorrenciaReenviada;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OcorrenciaService
{
    public function __construct(
        private FotoService $fotos,
        private NumeracaoService $numeracao,
    ) {}

    /**
     * Registra a ocorrência (status pendente), gera o número, salva as fotos
     * e notifica supervisores (e gerentes, se crítica).
     *
     * @param  array<int, UploadedFile>  $fotos
     */
    public function registrar(array $dados, array $fotos, User $user): Ocorrencia
    {
        $ocorrencia = DB::transaction(function () use ($dados, $fotos, $user) {
            $ocorrencia = Ocorrencia::create([
                ...$dados,
                'user_id' => $user->id,
                'criticidade' => $dados['criticidade_sugerida'],
                'status' => StatusOcorrencia::Pendente,
                'reenvios' => 0,
            ]);

            $this->numeracao->atribuir($ocorrencia, 'OC');
            $this->fotos->salvarVarias($fotos, $ocorrencia, TipoFoto::Antes, $user);

            return $ocorrencia;
        });

        $this->notificarAnalistas($ocorrencia->load('area'), new NovaOcorrencia($ocorrencia));

        return $ocorrencia;
    }

    /**
     * Correção de ocorrência reprovada: volta a pendente, incrementa reenvios
     * e notifica os supervisores novamente.
     *
     * @param  array<int, UploadedFile>  $novasFotos
     * @param  array<int, int>  $removerFotos
     */
    public function reenviar(Ocorrencia $ocorrencia, array $dados, array $novasFotos, array $removerFotos, User $user): Ocorrencia
    {
        $ocorrencia = DB::transaction(function () use ($ocorrencia, $dados, $novasFotos, $removerFotos, $user) {
            $ocorrencia = Ocorrencia::lockForUpdate()->findOrFail($ocorrencia->id);

            if ($ocorrencia->status !== StatusOcorrencia::Reprovada) {
                throw ValidationException::withMessages(['geral' => 'Somente ocorrências reprovadas podem ser reenviadas.']);
            }

            $ocorrencia->fill([
                ...$dados,
                'criticidade' => $dados['criticidade_sugerida'],
                'status' => StatusOcorrencia::Pendente,
                'reenvios' => $ocorrencia->reenvios + 1,
            ])->save();

            $ocorrencia->fotosAntes()->whereIn('id', $removerFotos)->get()
                ->each(fn ($foto) => $this->fotos->remover($foto));

            $this->fotos->salvarVarias($novasFotos, $ocorrencia, TipoFoto::Antes, $user);

            return $ocorrencia;
        });

        $this->notificarAnalistas($ocorrencia->load('area'), new OcorrenciaReenviada($ocorrencia));

        return $ocorrencia;
    }

    public function cancelar(Ocorrencia $ocorrencia): Ocorrencia
    {
        return DB::transaction(function () use ($ocorrencia) {
            $ocorrencia = Ocorrencia::lockForUpdate()->findOrFail($ocorrencia->id);

            if (! in_array($ocorrencia->status, [StatusOcorrencia::Pendente, StatusOcorrencia::Reprovada], true)) {
                throw ValidationException::withMessages(['geral' => 'Esta ocorrência não pode mais ser cancelada.']);
            }

            $ocorrencia->update(['status' => StatusOcorrencia::Cancelada]);

            return $ocorrencia;
        });
    }

    private function notificarAnalistas(Ocorrencia $ocorrencia, $notificacao): void
    {
        $roles = [Role::Supervisor];
        if ($ocorrencia->criticidade === Criticidade::Critica) {
            $roles[] = Role::Gerente;
        }

        $destinatarios = User::ativos()->comRole(...$roles)->get();

        Notification::send($destinatarios, $notificacao);
    }
}
