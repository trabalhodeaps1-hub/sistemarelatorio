<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Area> */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('A???')),
            'nome' => 'Área '.fake()->unique()->word(),
            'descricao' => fake()->sentence(),
            'ativo' => true,
        ];
    }
}
