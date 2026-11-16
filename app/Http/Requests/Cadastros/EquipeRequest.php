<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Descarta linhas de membros totalmente vazias
        $membros = collect($this->input('membros', []))
            ->filter(fn ($m) => filled($m['nome'] ?? null) || filled($m['matricula'] ?? null))
            ->values()
            ->all();

        $this->merge(['ativo' => $this->boolean('ativo'), 'membros' => $membros]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('equipes')->ignore($this->route('equipe'))],
            'especialidade' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
            'membros' => ['array', 'max:50'],
            'membros.*.nome' => ['required', 'string', 'max:255'],
            'membros.*.matricula' => ['nullable', 'string', 'max:30', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return [
            'membros.*.nome' => 'nome do membro',
            'membros.*.matricula' => 'matrícula do membro',
        ];
    }
}
