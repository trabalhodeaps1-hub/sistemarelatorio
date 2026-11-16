<?php

namespace Database\Factories;

use App\Models\MotivoReprovacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MotivoReprovacao> */
class MotivoReprovacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'descricao' => fake()->unique()->sentence(3),
            'exige_comentario' => false,
            'ordem' => fake()->numberBetween(1, 50),
            'ativo' => true,
        ];
    }

    public function exigeComentario(): static
    {
        return $this->state(fn () => ['exige_comentario' => true]);
    }
}
