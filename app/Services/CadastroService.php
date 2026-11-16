<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Regras comuns dos cadastros: nunca há exclusão física.
 * Registros com vínculos só podem ser desativados; sem vínculos, vão para a lixeira (soft delete).
 */
class CadastroService
{
    /** @return string mensagem de sucesso */
    public function excluir(Model $model, string $rotulo): string
    {
        if (method_exists($model, 'possuiVinculos') && $model->possuiVinculos()) {
            throw ValidationException::withMessages([
                'excluir' => "Este {$rotulo} possui registros vinculados e não pode ser excluído. Desative-o.",
            ]);
        }

        $model->delete();

        return ucfirst($rotulo).' excluído com sucesso.';
    }

    public function alternarAtivo(Model $model, string $rotulo): string
    {
        $model->update(['ativo' => ! $model->ativo]);

        return ucfirst($rotulo).($model->ativo ? ' ativado.' : ' desativado.');
    }
}
