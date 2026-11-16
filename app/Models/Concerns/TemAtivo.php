<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait TemAtivo
{
    public function initializeTemAtivo(): void
    {
        $this->mergeCasts(['ativo' => 'boolean']);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('ativo'), true);
    }

    /** Filtro padrão das listagens: busca textual + ativo/inativo. */
    public function scopeFiltroLista(Builder $query, ?string $busca, ?string $situacao, array $colunas): Builder
    {
        return $query
            ->when($busca, function (Builder $q) use ($busca, $colunas) {
                $q->where(function (Builder $w) use ($busca, $colunas) {
                    foreach ($colunas as $coluna) {
                        $w->orWhere($coluna, 'ilike', '%'.$busca.'%');
                    }
                });
            })
            ->when($situacao === 'ativos', fn (Builder $q) => $q->where('ativo', true))
            ->when($situacao === 'inativos', fn (Builder $q) => $q->where('ativo', false));
    }
}
