<?php

namespace App\Models;

use App\Enums\Criticidade;
use App\Enums\StatusOcorrencia;
use App\Enums\TipoFoto;
use App\Models\Concerns\RegistraAtividade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ocorrencia extends Model
{
    use HasFactory, RegistraAtividade, SoftDeletes;

    protected $fillable = [
        'numero', 'user_id', 'area_id', 'equipamento_id', 'categoria_id',
        'titulo', 'descricao', 'local', 'criticidade_sugerida', 'criticidade',
        'risco_pessoas', 'equipamento_parado', 'observacoes', 'status', 'reenvios',
    ];

    protected $attributes = [
        'status' => 'pendente',
        'reenvios' => 0,
    ];

    protected function casts(): array
    {
        return [
            'criticidade_sugerida' => Criticidade::class,
            'criticidade' => Criticidade::class,
            'status' => StatusOcorrencia::class,
            'risco_pessoas' => 'boolean',
            'equipamento_parado' => 'boolean',
            'reenvios' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class)->withTrashed();
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class)->withTrashed();
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class)->orderBy('id');
    }

    public function fotosAntes(): HasMany
    {
        return $this->fotos()->where('tipo', TipoFoto::Antes->value);
    }

    public function fotosDepois(): HasMany
    {
        return $this->fotos()->where('tipo', TipoFoto::Depois->value);
    }

    public function analises(): HasMany
    {
        return $this->hasMany(OcorrenciaAnalise::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function ultimaAnalise(): HasOne
    {
        return $this->hasOne(OcorrenciaAnalise::class)->latestOfMany();
    }

    public function ordemServico(): HasOne
    {
        return $this->hasOne(OrdemServico::class);
    }

    public function scopeStatus(Builder $query, StatusOcorrencia|string|null $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status instanceof StatusOcorrencia ? $status->value : $status);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', StatusOcorrencia::Pendente->value);
    }

    /** Ordena por criticidade (decrescente) e data (crescente). */
    public function scopeOrdemDeFila(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE criticidade WHEN 'critica' THEN 4 WHEN 'alta' THEN 3 WHEN 'media' THEN 2 ELSE 1 END DESC")
            ->orderBy('created_at');
    }

    public function getRotuloAttribute(): string
    {
        return "{$this->numero} — {$this->titulo}";
    }
}
