<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\OrdemServico;
use App\Models\User;

class OrdemServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Gerente, Role::Master);
    }

    public function view(User $user, OrdemServico $os): bool
    {
        return $user->hasRole(Role::Gerente, Role::Master);
    }

    /** Gerente (ou master) movimenta a OS; a validade da transição é checada pela máquina de estados. */
    public function transicionar(User $user, OrdemServico $os): bool
    {
        return $user->hasRole(Role::Gerente, Role::Master);
    }
}
