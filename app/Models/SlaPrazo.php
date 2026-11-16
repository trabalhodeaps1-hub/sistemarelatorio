<?php

namespace App\Models;

use App\Enums\Criticidade;
use App\Models\Concerns\RegistraAtividade;
use Illuminate\Database\Eloquent\Model;

class SlaPrazo extends Model
{
    use RegistraAtividade;

    protected $table = 'sla_prazos';

    protected $fillable = ['criticidade', 'horas'];

    protected function casts(): array
    {
        return [
            'criticidade' => Criticidade::class,
            'horas' => 'integer',
        ];
    }

    /** Horas de SLA para a criticidade (com fallback padrão). */
    public static function horasPara(Criticidade $criticidade): int
    {
        $padrao = [
            Criticidade::Critica->value => 24,
            Criticidade::Alta->value => 72,
            Criticidade::Media->value => 168,
            Criticidade::Baixa->value => 360,
        ];

        return (int) (static::where('criticidade', $criticidade->value)->value('horas') ?? $padrao[$criticidade->value]);
    }
}
