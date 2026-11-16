<?php

namespace Database\Factories;

use App\Models\Equipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipe> */
class EquipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Equipe '.fake()->unique()->word(),
            'especialidade' => fake()->word(),
            'ativo' => true,
        ];
    }
}
