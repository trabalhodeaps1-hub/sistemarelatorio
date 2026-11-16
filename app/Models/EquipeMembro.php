<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipeMembro extends Model
{
    protected $table = 'equipe_membros';

    protected $fillable = ['equipe_id', 'nome', 'matricula'];

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }
}
