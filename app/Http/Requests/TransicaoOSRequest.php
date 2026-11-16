<?php

namespace App\Http\Requests;

use App\Enums\StatusOS;
use App\Rules\ImagemReal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransicaoOSRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transicionar', $this->route('ordem_servico'));
    }

    /** Transição fora da máquina de estados → 422, antes de validar os campos. */
    protected function prepareForValidation(): void
    {
        $novo = StatusOS::tryFrom((string) $this->input('status'));
        $os = $this->route('ordem_servico');

        if ($novo && ! $os->status->podeTransicionarPara($novo)) {
            abort(422, "Transição inválida: {$os->status->label()} → {$novo->label()}.");
        }
    }

    public function rules(): array
    {
        $kb = (int) config('sistema.fotos.max_kb', 10240);
        $max = (int) config('sistema.fotos.max_por_envio', 5);
        $status = $this->input('status');

        return [
            'status' => ['required', Rule::enum(StatusOS::class)],
            'equipe_id' => [
                Rule::requiredIf($status === StatusOS::Programada->value), 'nullable', 'integer',
                Rule::exists('equipes', 'id')->where('ativo', true)->whereNull('deleted_at'),
            ],
            'prazo_previsto' => ['nullable', 'date'],
            'comentario' => [Rule::requiredIf($status === StatusOS::Cancelada->value), 'nullable', 'string', 'max:2000'],
            'observacao_conclusao' => [Rule::requiredIf($status === StatusOS::Concluida->value), 'nullable', 'string', 'max:5000'],
            'fotos' => ['nullable', 'array', 'max:'.$max],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.$kb, new ImagemReal],
        ];
    }

    public function attributes(): array
    {
        return [
            'equipe_id' => 'equipe',
            'prazo_previsto' => 'prazo previsto',
            'comentario' => 'justificativa/comentário',
            'observacao_conclusao' => 'observação de conclusão',
            'fotos.*' => 'foto',
        ];
    }

    public function messages(): array
    {
        return [
            'equipe_id.required' => 'Informe a equipe para programar a OS.',
            'comentario.required' => 'Informe a justificativa do cancelamento.',
            'observacao_conclusao.required' => 'Descreva o serviço executado para concluir a OS.',
        ];
    }

    public function novoStatus(): StatusOS
    {
        return StatusOS::from($this->validated('status'));
    }
}
