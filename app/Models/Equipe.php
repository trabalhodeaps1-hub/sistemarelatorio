<?php

namespace App\Models;

use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipe extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $fillable = ['nome', 'especialidade', 'ativo'];

    public function membros(): HasMany
    {
        return $this->hasMany(EquipeMembro::class)->orderBy('nome');
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->ordensServico()->exists();
    }
}
