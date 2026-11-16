<?php

namespace Database\Seeders;

use App\Enums\Criticidade as C;
use App\Models\Area;
use App\Models\Equipamento;
use Illuminate\Database\Seeder;

class AreaEquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->dados() as $dadosArea) {
            $area = Area::updateOrCreate(
                ['codigo' => $dadosArea['codigo']],
                ['nome' => $dadosArea['nome'], 'descricao' => $dadosArea['descricao'], 'ativo' => true],
            );

            foreach ($dadosArea['equipamentos'] as [$tag, $nome, $criticidade]) {
                Equipamento::updateOrCreate(
                    ['tag' => $tag],
                    ['area_id' => $area->id, 'nome' => $nome, 'criticidade' => $criticidade, 'ativo' => true],
                );
            }
        }
    }

    private function dados(): array
    {
        return [
            [
                'codigo' => 'PMP', 'nome' => 'Pátio de Matérias-Primas',
                'descricao' => 'Recebimento e estocagem de minério, carvão e fundentes',
                'equipamentos' => [
                    ['PMP-VIR-01', 'Virador de vagões', C::Alta],
                    ['PMP-EMP-01', 'Empilhadeira de minério', C::Media],
                    ['PMP-REC-01', 'Recuperadora de minério', C::Media],
                    ['PMP-TC-01', 'Correia transportadora TC-01', C::Alta],
                ],
            ],
            [
                'codigo' => 'SIN', 'nome' => 'Sinterização',
                'descricao' => 'Produção de sínter para o alto-forno',
                'equipamentos' => [
                    ['SIN-MAQ-01', 'Máquina de sinterização', C::Critica],
                    ['SIN-EXA-01', 'Exaustor principal', C::Critica],
                    ['SIN-BRI-01', 'Britador de sínter', C::Alta],
                    ['SIN-RES-01', 'Resfriador circular', C::Alta],
                ],
            ],
            [
                'codigo' => 'COQ', 'nome' => 'Coqueria',
                'descricao' => 'Produção de coque metalúrgico',
                'equipamentos' => [
                    ['COQ-BAT-01', 'Bateria de fornos 1', C::Critica],
                    ['COQ-DES-01', 'Máquina desenfornadora', C::Alta],
                    ['COQ-CAR-01', 'Carro de apagamento', C::Alta],
                    ['COQ-EXG-01', 'Exaustor de gás de coqueria', C::Critica],
                ],
            ],
            [
                'codigo' => 'AF1', 'nome' => 'Alto-Forno 1',
                'descricao' => 'Produção de ferro-gusa',
                'equipamentos' => [
                    ['AF1-SOP-01', 'Soprador de ar', C::Critica],
                    ['AF1-REG-01', 'Regenerador (Cowper) 1', C::Critica],
                    ['AF1-CRG-01', 'Correia de carregamento do topo', C::Alta],
                    ['AF1-BOM-01', 'Bomba de água de refrigeração', C::Critica],
                    ['AF1-PTE-01', 'Ponte rolante da casa de corrida', C::Alta],
                ],
            ],
            [
                'codigo' => 'ACI', 'nome' => 'Aciaria',
                'descricao' => 'Conversão do gusa em aço (LD)',
                'equipamentos' => [
                    ['ACI-CNV-01', 'Convertedor LD 1', C::Critica],
                    ['ACI-FP-01', 'Forno panela', C::Alta],
                    ['ACI-PTE-01', 'Ponte rolante de panela', C::Critica],
                    ['ACI-DSP-01', 'Sistema de despoeiramento', C::Alta],
                ],
            ],
            [
                'codigo' => 'LC', 'nome' => 'Lingotamento Contínuo',
                'descricao' => 'Solidificação do aço em placas',
                'equipamentos' => [
                    ['LC-MLC-01', 'Máquina de lingotamento contínuo 1', C::Critica],
                    ['LC-DIS-01', 'Distribuidor (tundish)', C::Alta],
                    ['LC-MES-01', 'Mesa de rolos de saída', C::Media],
                    ['LC-OXI-01', 'Máquina de oxicorte', C::Media],
                ],
            ],
            [
                'codigo' => 'LTQ', 'nome' => 'Laminação a Quente',
                'descricao' => 'Laminação de placas em bobinas a quente',
                'equipamentos' => [
                    ['LTQ-FOR-01', 'Forno de reaquecimento', C::Critica],
                    ['LTQ-DES-01', 'Laminador desbastador', C::Critica],
                    ['LTQ-ACB-01', 'Trem acabador', C::Critica],
                    ['LTQ-BOB-01', 'Bobinadeira', C::Alta],
                    ['LTQ-HID-01', 'Unidade hidráulica de descarepação', C::Alta],
                ],
            ],
            [
                'codigo' => 'UTI', 'nome' => 'Utilidades',
                'descricao' => 'Água, ar comprimido, vapor e gases',
                'equipamentos' => [
                    ['UTI-CMP-01', 'Compressor de ar 1', C::Alta],
                    ['UTI-TRF-01', 'Torre de resfriamento 1', C::Alta],
                    ['UTI-BOM-01', 'Bomba de recalque de água industrial', C::Media],
                    ['UTI-ETA-01', 'Estação de tratamento de água', C::Media],
                ],
            ],
            [
                'codigo' => 'SUB', 'nome' => 'Subestação Elétrica',
                'descricao' => 'Distribuição de energia da planta',
                'equipamentos' => [
                    ['SUB-TRF-01', 'Transformador principal 138kV', C::Critica],
                    ['SUB-PMT-01', 'Painel de média tensão', C::Alta],
                    ['SUB-CAP-01', 'Banco de capacitores', C::Media],
                ],
            ],
            [
                'codigo' => 'EXP', 'nome' => 'Expedição',
                'descricao' => 'Armazenagem e carregamento de produtos',
                'equipamentos' => [
                    ['EXP-PTE-01', 'Ponte rolante de bobinas', C::Alta],
                    ['EXP-BAL-01', 'Balança rodoviária', C::Baixa],
                    ['EXP-EMP-01', 'Empilhadeira 16t', C::Media],
                ],
            ],
        ];
    }
}
