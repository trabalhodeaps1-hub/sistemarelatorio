<?php

namespace Database\Factories;

use App\Enums\Criticidade;
use App\Models\Area;
use App\Models\Equipamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipamento> */
class EquipamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'tag' => strtoupper(fake()->unique()->bothify('EQ-???-##')),
            'nome' => 'Equipamento '.fake()->word(),
            'criticidade' => fake()->randomElement(Criticidade::cases()),
            'ativo' => true,
        ];
    }
}
