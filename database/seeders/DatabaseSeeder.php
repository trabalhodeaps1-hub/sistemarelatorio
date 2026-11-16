<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SlaPrazoSeeder::class,
            CategoriaSeeder::class,
            MotivoReprovacaoSeeder::class,
            AreaEquipamentoSeeder::class,
            EquipeSeeder::class,
            MasterUserSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
