<?php

namespace App\Http\Requests\Cadastros;

use App\Enums\Criticidade;
use Illuminate\Foundation\Http\FormRequest;

class SlaPrazoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    public function rules(): array
    {
        $regras = ['horas' => ['required', 'array']];

        foreach (Criticidade::cases() as $c) {
            $regras["horas.{$c->value}"] = ['required', 'integer', 'min:1', 'max:8760'];
        }

        return $regras;
    }

    public function attributes(): array
    {
        return collect(Criticidade::cases())
            ->mapWithKeys(fn (Criticidade $c) => ["horas.{$c->value}" => 'prazo ('.$c->label().')'])
            ->all();
    }
}
