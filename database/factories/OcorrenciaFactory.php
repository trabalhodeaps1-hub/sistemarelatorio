<?php

namespace Database\Factories;

use App\Enums\Criticidade;
use App\Enums\Role;
use App\Enums\StatusOcorrencia;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Ocorrencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ocorrencia> */
class OcorrenciaFactory extends Factory
{
    public function definition(): array
    {
        $criticidade = fake()->randomElement(Criticidade::cases());

        return [
            'user_id' => User::factory()->role(Role::Tecnico),
            'area_id' => Area::factory(),
            'equipamento_id' => null,
            'categoria_id' => Categoria::factory(),
            'titulo' => 'Vazamento '.fake()->words(3, true),
            'descricao' => fake()->paragraph(4),
            'local' => fake()->streetName(),
            'criticidade_sugerida' => $criticidade,
            'criticidade' => $criticidade,
            'risco_pessoas' => false,
            'equipamento_parado' => false,
            'status' => StatusOcorrencia::Pendente,
            'reenvios' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Ocorrencia $o) {
            if (! $o->numero) {
                $o->forceFill(['numero' => sprintf('OC-%s-%06d', $o->created_at->format('Y'), $o->id)])->saveQuietly();
            }
        });
    }

    public function criticidade(Criticidade $c): static
    {
        return $this->state(fn () => ['criticidade' => $c, 'criticidade_sugerida' => $c]);
    }

    public function status(StatusOcorrencia $s): static
    {
        return $this->state(fn () => ['status' => $s]);
    }
}
