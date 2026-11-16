<?php

namespace App\Models;

use App\Enums\TipoFoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Foto extends Model
{
    protected $fillable = ['ocorrencia_id', 'tipo', 'path', 'tamanho', 'user_id'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoFoto::class,
            'tamanho' => 'integer',
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

    public function url(): string
    {
        return route('fotos.show', $this);
    }

    /** Conteúdo em data URI (base64), usado no PDF. */
    public function dataUri(): ?string
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($disk->get($this->path));
    }
}
