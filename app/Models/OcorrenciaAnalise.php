<?php

namespace App\Models;

use App\Enums\StatusOcorrencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcorrenciaAnalise extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ocorrencia_analises';

    protected $fillable = ['ocorrencia_id', 'user_id', 'acao', 'motivo_reprovacao_id', 'comentario'];

    protected function casts(): array
    {
        return [
            'acao' => StatusOcorrencia::class,
            'created_at' => 'datetime',
        ];
    }

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(Ocorrencia::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoReprovacao::class, 'motivo_reprovacao_id')->withTrashed();
    }
}
