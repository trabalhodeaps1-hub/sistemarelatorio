# Roadmap — AutoRelatório Industrial

Nov 16, 2026 · @Weslley Dutra

## Premissas e regras gerais

Roadmap em 12 fases (0 a 11), entregue em 5 sprints, para o agente do VS Code executar fase a fase.

**Premissas assumidas** (ajustar se mudar):

- Equipe técnica é apenas cadastro, sem login.
- Supervisor e gerente atuam em todas as áreas.
- O gerente conclui a OS; `Concluída` é o status final, sem validação do supervisor.
- "Nova Inspeção" fica fora da v1. O botão vira "Apontar Problema" (registro de ocorrência).
- Fotos armazenadas localmente.

**Regras para o agente:**

- **Laravel 12:** não existe `app/Http/Kernel.php` nem `app/Console/Kernel.php`. Middleware vai em `bootstrap/app.php` (`->withMiddleware()`) e agendamentos em `routes/console.php`.
- **Front:** Blade + Bootstrap 5.3 + JS puro. Sem Vue, React, Livewire ou Alpine.
- **Organização:** controllers enxutos, lógica em `app/Services`, validação em `FormRequest`, autorização em `Policies`, status e perfis como PHP Enums em `app/Enums`.
- **Nomes:** domínio em português (tabelas, models, rotas), estrutura no padrão Laravel.
- **Cadastros:** `SoftDeletes` e flag `ativo` em todos; nunca exclusão física.
- **Fotos:** disco `local` (`storage/app/private`), servidas por controller com Policy; nunca na pasta pública.
- **Testes:** cada fase termina com testes de feature (Pest) passando.

## Fase 0: Setup

1. `laravel new autorelatorio` (PHP 8.4, Pest, sem starter kit).
2. Ambiente local no **Laragon**:
   - PHP 8.4 selecionado, com as extensões `pdo_pgsql`, `pgsql` e `gd` habilitadas no `php.ini`.
   - PostgreSQL ativo; criar o banco `autorelatorio`.
   - Docker fica só para o deploy (Fase 11).
3. `.env`: `DB_CONNECTION=pgsql`, `DB_PORT=5432`, `DB_DATABASE=autorelatorio`, `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR`. Timezone `America/Sao_Paulo` em `config/app.php`.
4. Tradução: `composer require lucascudo/laravel-pt-br-localization --dev` e publicar.
5. Pacotes PHP: `composer require intervention/image barryvdh/laravel-dompdf`.
6. Front: `npm i bootstrap @popperjs/core bootstrap-icons sass -D`.
   - Criar `resources/scss/app.scss` com a paleta: `$primary: #0078DC; $info: #0086C8; $success: #00986D; $warning: #FFC24E; $body-bg: #F3F4F7`.
   - Ajustar o `vite.config.js`.
7. `resources/js/app.js` importa o Bootstrap JS e os módulos JS da aplicação.

**Pronto quando:** o site abre pelo Laragon, a página inicial carrega com Bootstrap estilizado e a conexão com o Postgres funciona.

## Fase 1: Banco de dados, Enums e Seeders

**Enums** (`app/Enums`), todos com `label()` e `badgeClass()`:

- `Role`: master, tecnico, supervisor, gerente
- `Criticidade`: baixa, media, alta, critica
- `StatusOcorrencia`: pendente, aprovada, reprovada, cancelada
- `StatusOS`: aberta, programada, em\_execucao, pausada, concluida, cancelada, com `transicoesPermitidas()`
- `TipoFoto`: antes, depois

**Tabelas:**

| Tabela | Campos principais |
| --- | --- |
| `users` | name, matricula (unique), email (unique), password, role, ativo, softDeletes |
| `areas` | codigo (unique), nome, descricao, ativo, softDeletes |
| `categorias` | nome, ativo |
| `equipamentos` | area\_id, tag (unique), nome, criticidade, ativo, softDeletes |
| `motivos_reprovacao` | descricao, exige\_comentario (bool), ordem, ativo |
| `equipes` | nome, especialidade, ativo |
| `equipe_membros` | equipe\_id, nome, matricula |
| `sla_prazos` | criticidade (unique), horas |
| `ocorrencias` | numero (unique), user\_id, area\_id, equipamento\_id (nullable), categoria\_id, titulo, descricao, local, criticidade\_sugerida, criticidade, risco\_pessoas, equipamento\_parado, observacoes, status, reenvios (int), softDeletes |
| `ocorrencia_analises` | ocorrencia\_id, user\_id, acao (aprovada/reprovada), motivo\_reprovacao\_id, comentario, created\_at |
| `fotos` | ocorrencia\_id, tipo (antes/depois), path, tamanho, user\_id |
| `ordens_servico` | numero (unique), ocorrencia\_id (unique), status, criticidade, equipe\_id, responsavel\_id, prazo\_previsto, iniciada\_em, concluida\_em, cancelada\_em, observacao\_conclusao |
| `os_historico` | ordem\_servico\_id, user\_id, status\_anterior, status\_novo, comentario, created\_at |
| `notifications` | via `php artisan make:notifications-table` |
| `activity_logs` | user\_id, acao, model\_type, model\_id, dados (json), ip, created\_at |

**Índices:** `ocorrencias(status, criticidade)`, `ordens_servico(status, prazo_previsto)` e todas as FKs.

**Numeração:** `OC-2026-000123` e `OS-2026-000123`, gerados a partir do `id` logo após o insert, dentro da transação.

**Seeders:**

- Usuário master (dados do `.env`).
- Categorias e motivos de reprovação (os 10 do escopo).
- SLA: crítica 24h, alta 72h, média 168h, baixa 360h.
- `DemoSeeder` (apenas dev): áreas, equipamentos, equipes e 1 usuário de cada perfil.

**Pronto quando:** `migrate:fresh --seed` roda sem erro e os models têm relações, casts de Enum e scopes (`ativos()`, `atrasadas()`).

## Fase 1.1: Seeders (código pronto)

Todos usam `updateOrCreate`, então rodar de novo não duplica registros. Resultado: 10 áreas, 40 equipamentos, 8 categorias, 10 motivos de reprovação, 6 equipes com membros, SLA, master e 4 usuários de teste (senha `password`).

**Pré-requisitos:**

- Enums `App\Enums\Criticidade` (`Baixa`, `Media`, `Alta`, `Critica`) e `App\Enums\Role` (`Master`, `Tecnico`, `Supervisor`, `Gerente`).
- Model `MotivoReprovacao` com `protected $table = 'motivos_reprovacao';`.
- Model `Equipe` com relação `membros()` (`hasMany(EquipeMembro::class)`).
- Model `User` com cast `'password' => 'hashed'` e `'role' => Role::class`.

**Rodar:** `php artisan migrate:fresh --seed`

### config/sistema.php

Dados do master em config (não `env()` no seeder), para funcionar com `config:cache`.

```php
<?php

return [
    'master' => [
        'name'      => env('MASTER_NAME', 'Administrador'),
        'email'     => env('MASTER_EMAIL', 'admin@autorelatorio.local'),
        'matricula' => env('MASTER_MATRICULA', 'M0001'),
        'password'  => env('MASTER_PASSWORD', 'password'),
    ],
];
```

### database/seeders/DatabaseSeeder.php

```php
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
```

### SlaPrazoSeeder.php

```php
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
```

### CategoriaSeeder.php

```php
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
```

### MotivoReprovacaoSeeder.php

```php
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
```

### AreaEquipamentoSeeder.php

```php
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
```

### EquipeSeeder.php

```php
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
```

### MasterUserSeeder.php

`firstOrCreate` de propósito: rodar o seed de novo não sobrescreve a senha do master.

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        $master = config('sistema.master');

        User::firstOrCreate(
            ['email' => $master['email']],
            [
                'name'      => $master['name'],
                'matricula' => $master['matricula'],
                'password'  => $master['password'],
                'role'      => Role::Master,
                'ativo'     => true,
            ],
        );
    }
}
```

### DemoUserSeeder.php (só em ambiente local)

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['Técnico Demo', 'tecnico@autorelatorio.local', 'T0001', Role::Tecnico],
            ['Técnico Demo 2', 'tecnico2@autorelatorio.local', 'T0002', Role::Tecnico],
            ['Supervisor Demo', 'supervisor@autorelatorio.local', 'S0001', Role::Supervisor],
            ['Gerente Demo', 'gerente@autorelatorio.local', 'G0001', Role::Gerente],
        ];

        foreach ($usuarios as [$nome, $email, $matricula, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $nome, 'matricula' => $matricula, 'password' => 'password', 'role' => $role, 'ativo' => true],
            );
        }
    }
}
```

## Fase 2: Autenticação, layout e perfis

1. **Login próprio** (`Auth/LoginController`): por e-mail ou matrícula, só usuários com `ativo = true`, rota com `throttle:5,1`, sem cadastro público.
2. **Middlewares** registrados em `bootstrap/app.php`:
   - `role` → `EnsureRole` (uso: `role:master,gerente`)
   - `EnsureUserIsActive` no grupo `web`, que desloga quem for desativado.
3. **Troca de senha** pelo próprio usuário; o master pode resetar a de qualquer um.
4. **Layout** `layouts/app.blade.php`: sidebar escura (`#07132B`) que vira `offcanvas-lg` no mobile, topbar com sino e avatar (perfil, sair), menu conforme o perfil.
5. **Componentes Blade:** `x-status-badge`, `x-stat-card`, `x-flash`, `x-form.input`, `x-form.select`, `x-form.textarea` (com erro), `x-empty-state`.
6. **Páginas de erro** 403, 404, 419 e 500 no padrão visual.

**Pronto quando:** cada perfil faz login, vê só o seu menu e recebe 403 em rotas de outro perfil (com teste).

## Fase 3: Cadastros (Master)

- Controllers resource com FormRequest para: usuários, áreas, equipamentos, categorias, motivos de reprovação, equipes (com membros) e SLA (apenas edição).
- Listas com busca, filtro ativo/inativo e `paginate(20)`.
- Itens com registros vinculados só podem ser desativados, não excluídos.
- Equipamentos com filtro por área.
- **Endpoint JSON** `GET /areas/{area}/equipamentos`: devolve os equipamentos ativos da área para o select dependente.
- Rotas no grupo `role:master`.

**Pronto quando:** CRUD completo funciona, validações em pt-BR e testes de acesso e validação passam.

## Fase 4: Ocorrências (Técnico)

1. **Formulário** `ocorrencias/create`, nesta ordem:
   - Área, depois equipamento (via fetch), com a opção "Estrutura / Não se aplica" que grava `equipamento_id = null`.
   - Categoria, título e descrição (mínimo 30 caracteres).
   - Local e criticidade sugerida.
   - Checkboxes: risco a pessoas, equipamento parado.
   - Observações.
   - Fotos: `<input type="file" accept="image/*" capture="environment" multiple>`, de 1 a 5, jpg/png/webp até 10MB cada.
2. **JS** `resources/js/fotos.js`: preview em miniatura, remoção individual e bloqueio acima de 5 fotos.
3. **`FotoService`:** Intervention reduz para no máximo 1600px (`scaleDown`), grava JPG qualidade 80 em `private/ocorrencias/{Y}/{m}/{uuid}.jpg`.
4. **`OcorrenciaService::registrar()`** em transação: cria a ocorrência, gera o número, salva as fotos e notifica os supervisores (e os gerentes, se crítica).
5. **Rota de fotos** `GET /fotos/{foto}` protegida por `FotoPolicy`.
6. **Páginas:** "Minhas ocorrências" com filtro por status; detalhe com galeria (modal Bootstrap) e histórico de análises.
7. **Reprovada:** o técnico dono edita e reenvia. Status volta a `pendente`, `reenvios++` e os supervisores são notificados de novo.
8. O técnico pode cancelar a própria ocorrência enquanto estiver `pendente`.

**Pronto quando:** o técnico registra pelo celular com câmera, as fotos chegam comprimidas e o reenvio funciona.

## Fase 5: Análise (Supervisor)

1. **Fila de pendentes** ordenada por criticidade (decrescente) e data (crescente), com críticas em destaque.
2. **Tela de análise** com dados completos, fotos e dois botões:
   - **Aprovar:** permite ajustar a criticidade; comentário opcional.
   - **Reprovar:** motivo obrigatório; comentário obrigatório quando o motivo tem `exige_comentario`.
3. **`AnaliseService`** em transação com `lockForUpdate()`, para evitar análise duplicada:
   - **Aprovar:** status `aprovada`, registro em `ocorrencia_analises`, cria OS `aberta` com `prazo_previsto = now + SLA`, histórico inicial da OS, notifica gerentes e técnico.
   - **Reprovar:** status `reprovada`, registro em `ocorrencia_analises`, notifica o técnico com o motivo.
4. **Policy:** só supervisor (ou master) analisa, e só ocorrências `pendente`.

**Pronto quando:** a aprovação gera a OS com prazo correto e a reprovação volta ao técnico com o motivo (com testes).

## Fase 6: Gestão de OS (Gerente)

1. **Lista** com filtros (status, área, equipe, criticidade) e badges "Atrasada" e "Vence em 24h".
2. **Detalhe da OS:** dados da ocorrência, fotos de antes, timeline do histórico e botões apenas para as transições válidas.
3. **Máquina de estados** em `StatusOS::transicoesPermitidas()`:

| De | Para | Exigência |
| --- | --- | --- |
| `aberta` | `programada` | equipe obrigatória, prazo ajustável |
| `aberta` | `cancelada` | justificativa |
| `programada` | `em_execucao`, `pausada` | — |
| `programada` | `cancelada` | justificativa |
| `em_execucao` | `pausada` | — |
| `em_execucao` | `concluida` | 1+ foto de "depois" e observação |
| `pausada` | `em_execucao` | — |
| `pausada` | `cancelada` | justificativa |

4. **`OrdemServicoService::transicionar()`:** valida a transição, preenche `iniciada_em`, `concluida_em` e `cancelada_em`, grava em `os_historico` e notifica o técnico na conclusão.

**Pronto quando:** transições inválidas retornam 422, o histórico fica completo e a OS concluída mostra fotos de antes e depois.

## Fase 7: Notificações e dashboard

1. **Notifications** com `via(['database'])`; `toArray()` retorna titulo, mensagem, url e nivel (info/alerta/critico). Classes: `NovaOcorrencia`, `OcorrenciaReenviada`, `OcorrenciaAprovada`, `OcorrenciaReprovada`, `OSConcluida`, `OSAtrasada`.
2. **Sino:**
   - Contador via View Composer e dropdown com as últimas 10.
   - Rotas: `/notificacoes` (lista), marcar como lida, marcar todas.
   - `notificacoes.js` faz polling em `GET /notificacoes/contador` a cada 60s.
3. **Atrasos:** comando `os:verificar-atrasos` agendado em `routes/console.php` (`->hourly()`), notificando o gerente uma única vez por OS.
4. **Dashboard por perfil** (cards no padrão do layout):
   - Técnico: pendentes, reprovadas para corrigir, aprovadas.
   - Supervisor: pendentes, críticas pendentes, analisadas hoje.
   - Gerente: abertas, em execução, atrasadas, vencendo em 24h, concluídas no mês.
   - Master: visão geral.
5. **Gráficos (opcional):** Chart.js via CDN com ocorrências por mês e top 5 equipamentos.

## Fase 8: Relatórios

1. **Filtros:** período obrigatório (máximo 12 meses), área, equipamento, status, criticidade e equipe.
2. **`RelatorioService`:** uma query por relatório, prévia em HTML e botão "Gerar PDF" (DomPDF, A4).
3. **Relatórios:**
   1. OS aprovadas (aprovador e data)
   2. Ocorrências reprovadas (motivo e quem reprovou)
   3. OS concluídas (prazo previsto x realizado, equipe)
   4. Ranking de equipamentos
   5. Ranking de áreas
   6. Indicadores de prazo (tempo médio de aprovação e de execução, OS em atraso)
   7. Ficha individual da OS (fotos de antes e depois, histórico)
4. **Layout PDF:** cabeçalho com título, filtros, quem gerou e data/hora; rodapé com paginação.
5. **Fotos no PDF:** embutir em base64 a partir do caminho privado, porque o DomPDF não acessa a rota protegida.
6. **Acesso:** master, supervisor e gerente.

## Fase 9: Auditoria, segurança e polimento

- Trait `RegistraAtividade` com observer nos models principais, gravando created/updated/deleted (com as mudanças) em `activity_logs`.
- Tela de logs para o master, com filtros.
- Revisar se todas as rotas estão cobertas por Policy ou middleware.
- Uploads validados por mime real.
- QA mobile em 360px: botões com no mínimo 44px, formulários em uma coluna, tabelas com `table-responsive` ou virando cards.

## Fase 10: Testes (consolidação)

Testes de feature com Pest cobrindo:

- Login, usuário inativo e throttle.
- Acesso de cada perfil a cada grupo de rotas.
- Fluxo completo: ocorrência → aprovação → OS → conclusão.
- Reprovação, reenvio e nova aprovação.
- Transições inválidas da OS.
- Compressão e acesso protegido das fotos.
- Todos os relatórios retornando 200 com PDF gerado.

## Fase 11: Deploy

1. **Docker de produção:** php-fpm com OPcache, nginx e postgres, com volumes para `storage/` e para os dados do Postgres.
2. **Limites de upload:** nginx `client_max_body_size 60M`; PHP `upload_max_filesize=10M`, `post_max_size=60M`.
3. **HTTPS obrigatório:** nginx + certbot.
4. **Deploy:** `migrate --force`, `config:cache`, `route:cache`, `view:cache`.
5. **Cron:** `* * * * * php artisan schedule:run`.
6. **Backup diário:** `pg_dump` do banco e `tar` de `storage/app/private`, retenção de 7 dias.

## Ordem de entrega

| Sprint | Fases |
| --- | --- |
| 1 | 0 Setup, 1 Banco de dados, 2 Autenticação e layout |
| 2 | 3 Cadastros, 4 Ocorrências |
| 3 | 5 Análise, 6 Gestão de OS |
| 4 | 7 Notificações e dashboard, 8 Relatórios |
| 5 | 9 Auditoria e polimento, 10 Testes, 11 Deploy |
