<?php

namespace App\Services;

use App\Models\Equipe;
use Illuminate\Support\Facades\DB;

class EquipeService
{
    /** Cria ou atualiza a equipe e sincroniza a lista de membros. */
    public function salvar(Equipe $equipe, array $dados): Equipe
    {
        return DB::transaction(function () use ($equipe, $dados) {
            $equipe->fill(collect($dados)->only(['nome', 'especialidade', 'ativo'])->all())->save();

            $equipe->membros()->delete();
            foreach ($dados['membros'] ?? [] as $membro) {
                $equipe->membros()->create([
                    'nome' => $membro['nome'],
                    'matricula' => $membro['matricula'] ?? null,
                ]);
            }

            return $equipe;
        });
    }
}
