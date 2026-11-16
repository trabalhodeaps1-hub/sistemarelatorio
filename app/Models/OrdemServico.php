<?php

namespace App\Models;

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use App\Models\Concerns\RegistraAtividade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory, RegistraAtividade;

    protected $table = 'ordens_servico';

    protected $fillable = [
        'numero', 'ocorrencia_id', 'status', 'criticidade', 'equipe_id', 'responsavel_id',
        'prazo_previsto', 'iniciada_em', 'concluida_em', 'cancelada_em',
        'atraso_notificado_em', 'observacao_conclusao',
    ];

    protected $attributes = [
        'status' => 'aberta',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusOS::class,
            'criticidade' => Criticidade::class,
            'prazo_previsto' => 'datetime',
            'iniciada_em' => 'datetime',
            'concluida_em' => 'datetime',
            'cancelada_em' => 'datetime',
            'atraso_notificado_em' => 'datetime',
        ];
    }

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(Ocorrencia::class)->withTrashed();
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class)->withTrashed();
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id')->withTrashed();
    }

    public function historico(): HasMany
    {
        return $this->hasMany(OsHistorico::class)->orderBy('created_at')->orderBy('id');
    }

    public function scopeEmAndamento(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (StatusOS $s) => $s->value, StatusOS::emAndamento()));
    }

    public function scopeAtrasadas(Builder $query): Builder
    {
        return $query->emAndamento()->whereNotNull('prazo_previsto')->where('prazo_previsto', '<', now());
    }

    public function scopeVencendoEm24h(Builder $query): Builder
    {
        return $query->emAndamento()->whereBetween('prazo_previsto', [now(), now()->addDay()]);
    }

    public function atrasada(): bool
    {
        if ($this->status->finalizada()) {
            return $this->status === StatusOS::Concluida
                && $this->prazo_previsto && $this->concluida_em
                && $this->concluida_em->gt($this->prazo_previsto);
        }

        return $this->prazo_previsto !== null && $this->prazo_previsto->isPast();
    }

    public function venceEm24h(): bool
    {
        return ! $this->status->finalizada()
            && $this->prazo_previsto !== null
            && $this->prazo_previsto->isFuture()
            && $this->prazo_previsto->lte(now()->addDay());
    }
}
