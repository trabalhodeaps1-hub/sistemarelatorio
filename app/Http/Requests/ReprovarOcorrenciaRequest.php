<?php

namespace App\Http\Requests;

use App\Models\MotivoReprovacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReprovarOcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analisar', $this->route('ocorrencia'));
    }

    public function rules(): array
    {
        return [
            'motivo_reprovacao_id' => [
                'required', 'integer',
                Rule::exists('motivos_reprovacao', 'id')->where('ativo', true)->whereNull('deleted_at'),
            ],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('motivo_reprovacao_id')) {
                    return;
                }
                $motivo = $this->motivo();
                if ($motivo?->exige_comentario && blank($this->input('comentario'))) {
                    $validator->errors()->add('comentario', 'O motivo selecionado exige um comentário.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['motivo_reprovacao_id' => 'motivo', 'comentario' => 'comentário'];
    }

    public function motivo(): ?MotivoReprovacao
    {
        return MotivoReprovacao::find($this->input('motivo_reprovacao_id'));
    }
}
