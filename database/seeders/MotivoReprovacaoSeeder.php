<?php

namespace Database\Seeders;

use App\Models\MotivoReprovacao;
use Illuminate\Database\Seeder;

class MotivoReprovacaoSeeder extends Seeder
{
    public function run(): void
    {
        // [descricao, exige_comentario]
        $motivos = [
            ['Área ou equipamento incorreto', false],
            ['Descrição incompleta ou confusa', false],
            ['Foto não evidencia o problema ou está ilegível', false],
            ['Ocorrência duplicada (já existe OS aberta)', false],
            ['Não procede (não é um problema)', true],
            ['Já resolvido no local', false],
            ['Fora do escopo da manutenção', true],
            ['Categoria ou criticidade incorreta', false],
            ['Localização insuficiente', false],
            ['Outro', true],
        ];

        foreach ($motivos as $i => [$descricao, $exigeComentario]) {
            MotivoReprovacao::updateOrCreate(
                ['descricao' => $descricao],
                ['exige_comentario' => $exigeComentario, 'ordem' => $i + 1, 'ativo' => true],
            );
        }
    }
}
