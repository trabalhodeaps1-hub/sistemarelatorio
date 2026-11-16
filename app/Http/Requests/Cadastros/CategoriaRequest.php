<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('categorias')->ignore($this->route('categoria'))],
            'ativo' => ['boolean'],
        ];
    }
}
