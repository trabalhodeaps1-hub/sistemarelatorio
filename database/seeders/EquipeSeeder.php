<?php

namespace Database\Seeders;

use App\Models\Equipe;
use Illuminate\Database\Seeder;

class EquipeSeeder extends Seeder
{
    public function run(): void
    {
        $equipes = [
            ['Mecânica', 'Manutenção mecânica', [['Carlos Souza', 'MC001'], ['João Pereira', 'MC002'], ['Rafael Lima', 'MC003']]],
            ['Elétrica', 'Manutenção elétrica', [['André Santos', 'EL001'], ['Marcos Oliveira', 'EL002']]],
            ['Caldeiraria', 'Soldagem e caldeiraria', [['Paulo Costa', 'CA001'], ['Diego Alves', 'CA002']]],
            ['Civil', 'Estruturas, pisos e acessos', [['Roberto Dias', 'CV001'], ['Fábio Rocha', 'CV002']]],
            ['Instrumentação', 'Instrumentação e automação', [['Lucas Martins', 'IN001']]],
            ['Lubrificação', 'Lubrificação e vedações', [['Tiago Ferreira', 'LB001']]],
        ];

        foreach ($equipes as [$nome, $especialidade, $membros]) {
            $equipe = Equipe::updateOrCreate(
                ['nome' => $nome],
                ['especialidade' => $especialidade, 'ativo' => true],
            );

            foreach ($membros as [$nomeMembro, $matricula]) {
                $equipe->membros()->updateOrCreate(['matricula' => $matricula], ['nome' => $nomeMembro]);
            }
        }
    }
}
