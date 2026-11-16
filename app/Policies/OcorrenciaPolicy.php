<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\StatusOcorrencia;
use App\Models\Ocorrencia;
use App\Models\User;

class OcorrenciaPolicy
{
    /** Dono, supervisores, gerentes e master enxergam a ocorrência. */
    public function view(User $user, Ocorrencia $ocorrencia): bool
    {
        return $ocorrencia->user_id === $user->id
            || $user->hasRole(Role::Master, Role::Supervisor, Role::Gerente);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Tecnico, Role::Master);
    }

    /** Só o dono edita, e só quando reprovada (para corrigir e reenviar). */
    public function update(User $user, Ocorrencia $ocorrencia): bool
    {
        return $ocorrencia->user_id === $user->id
            && $ocorrencia->status === StatusOcorrencia::Reprovada;
    }

    /** O dono cancela enquanto estiver pendente (ou reprovada, desistindo do reenvio). */
    public function cancelar(User $user, Ocorrencia $ocorrencia): bool
    {
        return $ocorrencia->user_id === $user->id
            && in_array($ocorrencia->status, [StatusOcorrencia::Pendente, StatusOcorrencia::Reprovada], true);
    }

    /** Supervisor (ou master) analisa, e só ocorrências pendentes. */
    public function analisar(User $user, Ocorrencia $ocorrencia): bool
    {
        return $user->hasRole(Role::Supervisor, Role::Master)
            && $ocorrencia->status === StatusOcorrencia::Pendente;
    }
}
