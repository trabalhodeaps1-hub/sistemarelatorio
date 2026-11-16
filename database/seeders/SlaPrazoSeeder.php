<?php

namespace Database\Seeders;

use App\Enums\Criticidade;
use App\Models\SlaPrazo;
use Illuminate\Database\Seeder;

class SlaPrazoSeeder extends Seeder
{
    public function run(): void
    {
        $prazos = [
            [Criticidade::Critica, 24],
            [Criticidade::Alta, 72],
            [Criticidade::Media, 168],
            [Criticidade::Baixa, 360],
        ];

        foreach ($prazos as [$criticidade, $horas]) {
            SlaPrazo::updateOrCreate(['criticidade' => $criticidade], ['horas' => $horas]);
        }
    }
}
