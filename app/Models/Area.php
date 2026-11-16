<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $fillable = ['codigo', 'nome', 'descricao', 'ativo'];

    public function equipamentos(): HasMany
    {
        return $this->hasMany(Equipamento::class);
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->equipamentos()->withTrashed()->exists()
            || $this->ocorrencias()->withTrashed()->exists();
    }

    public function getNomeCompletoAttribute(): string
    {
        return "{$this->codigo} — {$this->nome}";
    }
}
