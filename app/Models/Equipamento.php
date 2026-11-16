<?php

namespace App\Models;

use App\Enums\Criticidade;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipamento extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $fillable = ['area_id', 'tag', 'nome', 'criticidade', 'ativo'];

    protected function casts(): array
    {
        return [
            'criticidade' => Criticidade::class,
            'ativo' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->ocorrencias()->withTrashed()->exists();
    }

    public function getNomeCompletoAttribute(): string
    {
        return "{$this->tag} — {$this->nome}";
    }
}
