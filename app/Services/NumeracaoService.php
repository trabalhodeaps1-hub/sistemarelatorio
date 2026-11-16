<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Gera números legíveis (OC-2026-000123 / OS-2026-000123) a partir do id,
 * logo após o insert e dentro da mesma transação.
 */
class NumeracaoService
{
    public function atribuir(Model $model, string $prefixo): string
    {
        $ano = ($model->created_at ?? now())->format('Y');
        $numero = sprintf('%s-%s-%06d', $prefixo, $ano, $model->getKey());

        $model->forceFill(['numero' => $numero])->saveQuietly();

        return $numero;
    }
}
