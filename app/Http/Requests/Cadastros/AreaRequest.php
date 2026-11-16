<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => mb_strtoupper(trim((string) $this->input('codigo'))),
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:20', Rule::unique('areas')->ignore($this->route('area'))],
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['codigo' => 'código', 'descricao' => 'descrição'];
    }
}
