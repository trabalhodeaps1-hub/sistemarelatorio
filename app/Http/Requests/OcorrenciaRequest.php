<?php

namespace App\Http\Requests;

use App\Enums\Criticidade;
use App\Models\Ocorrencia;
use App\Rules\ImagemReal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Usado no registro (store) e na correção/reenvio (update) da ocorrência.
 * equipamento_id = 0 significa "Estrutura / Não se aplica" (grava null).
 */
class OcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ocorrencia = $this->route('ocorrencia');

        return $ocorrencia
            ? $this->user()->can('update', $ocorrencia)
            : $this->user()->can('create', Ocorrencia::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'risco_pessoas' => $this->boolean('risco_pessoas'),
            'equipamento_parado' => $this->boolean('equipamento_parado'),
        ]);
    }

    public function rules(): array
    {
        $max = (int) config('sistema.fotos.max_por_envio', 5);
        $kb = (int) config('sistema.fotos.max_kb', 10240);
        $editando = $this->route('ocorrencia') !== null;

        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('ativo', true)->whereNull('deleted_at')],
            'equipamento_id' => [
                'required', 'integer',
                Rule::when((int) $this->input('equipamento_id') !== 0, [
                    Rule::exists('equipamentos', 'id')
                        ->where('area_id', (int) $this->input('area_id'))
                        ->where('ativo', true)
                        ->whereNull('deleted_at'),
                ]),
            ],
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('ativo', true)->whereNull('deleted_at')],
            'titulo' => ['required', 'string', 'min:5', 'max:120'],
            'descricao' => ['required', 'string', 'min:30', 'max:5000'],
            'local' => ['nullable', 'string', 'max:255'],
            'criticidade_sugerida' => ['required', Rule::enum(Criticidade::class)],
            'risco_pessoas' => ['boolean'],
            'equipamento_parado' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'fotos' => [$editando ? 'nullable' : 'required', 'array', 'max:'.$max],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.$kb, new ImagemReal],
            'remover_fotos' => ['nullable', 'array'],
            'remover_fotos.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ocorrencia = $this->route('ocorrencia');
                if (! $ocorrencia || $validator->errors()->has('fotos')) {
                    return;
                }

                // No reenvio, o total final de fotos deve continuar entre 1 e o máximo
                $max = (int) config('sistema.fotos.max_por_envio', 5);
                $remover = collect($this->input('remover_fotos', []))->map(fn ($id) => (int) $id);
                $restantes = $ocorrencia->fotosAntes()->whereNotIn('id', $remover)->count();
                $total = $restantes + count($this->file('fotos', []));

                if ($total < 1) {
                    $validator->errors()->add('fotos', 'A ocorrência precisa de pelo menos 1 foto.');
                } elseif ($total > $max) {
                    $validator->errors()->add('fotos', "A ocorrência pode ter no máximo {$max} fotos (atualmente ficaria com {$total}).");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'area_id' => 'área',
            'equipamento_id' => 'equipamento',
            'categoria_id' => 'categoria',
            'titulo' => 'título',
            'descricao' => 'descrição',
            'criticidade_sugerida' => 'criticidade',
            'observacoes' => 'observações',
            'fotos.*' => 'foto',
        ];
    }

    public function messages(): array
    {
        return [
            'fotos.required' => 'Envie pelo menos 1 foto do problema.',
            'fotos.max' => 'Envie no máximo :max fotos.',
            'fotos.*.max' => 'Cada foto pode ter no máximo 10 MB.',
            'fotos.*.mimes' => 'As fotos devem ser JPG, PNG ou WEBP.',
            'fotos.*.mimetypes' => 'As fotos devem ser JPG, PNG ou WEBP.',
        ];
    }

    /** Dados prontos para gravar (sem arquivos). */
    public function dados(): array
    {
        $dados = $this->safe()->except(['fotos', 'remover_fotos']);
        $dados['equipamento_id'] = (int) $dados['equipamento_id'] ?: null;

        return $dados;
    }
}
