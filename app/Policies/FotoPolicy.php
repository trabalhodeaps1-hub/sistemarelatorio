<?php

namespace App\Policies;

use App\Models\Foto;
use App\Models\User;

class FotoPolicy
{
    /** Quem pode ver a ocorrência pode ver suas fotos. */
    public function view(User $user, Foto $foto): bool
    {
        return $user->can('view', $foto->ocorrencia);
    }
}
