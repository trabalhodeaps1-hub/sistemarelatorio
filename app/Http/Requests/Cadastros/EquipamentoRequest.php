<?php

namespace App\Http\Requests\Cadastros;

use App\Enums\Criticidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tag' => mb_strtoupper(trim((string) $this->input('tag'))),
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', Rule::exists('areas', 'id')->whereNull('deleted_at')],
            'tag' => ['required', 'string', 'max:40', Rule::unique('equipamentos')->ignore($this->route('equipamento'))],
            'nome' => ['required', 'string', 'max:255'],
            'criticidade' => ['required', Rule::enum(Criticidade::class)],
            'ativo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['area_id' => 'área'];
    }
}
