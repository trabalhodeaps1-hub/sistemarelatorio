<?php

namespace App\Models;

use App\Enums\StatusOS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsHistorico extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'os_historico';

    protected $fillable = ['ordem_servico_id', 'user_id', 'status_anterior', 'status_novo', 'comentario'];

    protected function casts(): array
    {
        return [
            'status_anterior' => StatusOS::class,
            'status_novo' => StatusOS::class,
            'created_at' => 'datetime',
        ];
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
