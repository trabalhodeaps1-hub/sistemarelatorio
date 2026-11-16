<?php

namespace App\Http\Requests;

use App\Enums\Criticidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AprovarOcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analisar', $this->route('ocorrencia'));
    }

    public function rules(): array
    {
        return [
            'criticidade' => ['required', Rule::enum(Criticidade::class)],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['comentario' => 'comentário'];
    }
}
