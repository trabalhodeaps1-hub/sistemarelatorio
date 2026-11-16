<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $fillable = ['nome', 'ativo'];

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->ocorrencias()->withTrashed()->exists();
    }
}
