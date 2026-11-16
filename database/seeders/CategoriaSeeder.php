<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Defeito em equipamento',
            'Estrutural (piso, escada, guarda-corpo, plataforma)',
            'Segurança do trabalho',
            'Vazamento (água, óleo, gás, vapor)',
            'Elétrica',
            'Instrumentação e automação',
            'Meio ambiente (emissão, derrame)',
            'Outros',
        ];

        foreach ($categorias as $nome) {
            Categoria::updateOrCreate(['nome' => $nome], ['ativo' => true]);
        }
    }
}
