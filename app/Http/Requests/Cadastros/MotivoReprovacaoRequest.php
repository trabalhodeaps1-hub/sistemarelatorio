<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;

class MotivoReprovacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
            'exige_comentario' => $this->boolean('exige_comentario'),
        ]);
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'exige_comentario' => ['boolean'],
            'ordem' => ['required', 'integer', 'min:0', 'max:999'],
            'ativo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['descricao' => 'descrição', 'exige_comentario' => 'exige comentário'];
    }
}
