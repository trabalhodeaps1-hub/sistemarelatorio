<?php

namespace App\Http\Requests;

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RelatorioFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // acesso controlado pelo middleware role:supervisor,gerente
    }

    public function rules(): array
    {
        return [
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'equipamento_id' => ['nullable', 'integer', 'exists:equipamentos,id'],
            'status' => ['nullable', Rule::enum(StatusOS::class)],
            'criticidade' => ['nullable', Rule::enum(Criticidade::class)],
            'equipe_id' => ['nullable', 'integer', 'exists:equipes,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['data_inicio', 'data_fim'])) {
                    return;
                }
                $max = (int) config('sistema.relatorios.max_meses', 12);
                $inicio = Carbon::parse($this->input('data_inicio'));
                $fim = Carbon::parse($this->input('data_fim'));

                if ($inicio->copy()->addMonthsNoOverflow($max)->lt($fim)) {
                    $validator->errors()->add('data_fim', "O período pode ter no máximo {$max} meses.");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'data_inicio' => 'data inicial',
            'data_fim' => 'data final',
            'area_id' => 'área',
            'equipamento_id' => 'equipamento',
            'equipe_id' => 'equipe',
        ];
    }

    /** @return array{inicio: Carbon, fim: Carbon, area_id: ?int, equipamento_id: ?int, status: ?string, criticidade: ?string, equipe_id: ?int} */
    public function filtros(): array
    {
        $v = $this->validated();

        return [
            'inicio' => Carbon::parse($v['data_inicio'])->startOfDay(),
            'fim' => Carbon::parse($v['data_fim'])->endOfDay(),
            'area_id' => isset($v['area_id']) ? (int) $v['area_id'] : null,
            'equipamento_id' => isset($v['equipamento_id']) ? (int) $v['equipamento_id'] : null,
            'status' => $v['status'] ?? null,
            'criticidade' => $v['criticidade'] ?? null,
            'equipe_id' => isset($v['equipe_id']) ? (int) $v['equipe_id'] : null,
        ];
    }
}
