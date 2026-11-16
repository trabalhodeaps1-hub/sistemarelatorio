<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoReprovacao extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $table = 'motivos_reprovacao';

    protected $fillable = ['descricao', 'exige_comentario', 'ordem', 'ativo'];

    protected function casts(): array
    {
        return [
            'exige_comentario' => 'boolean',
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function analises(): HasMany
    {
        return $this->hasMany(OcorrenciaAnalise::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->analises()->exists();
    }
}
