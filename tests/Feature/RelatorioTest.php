<?php

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use App\Models\Equipamento;
use App\Models\Equipe;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Models\User;
use App\Services\AnaliseService;
use App\Services\OrdemServicoService;
use App\Services\RelatorioService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    $this->gerente = User::factory()->gerente()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->equipamento = Equipamento::factory()->create(['tag' => 'BOMBA-01']);
    $this->equipe = Equipe::factory()->create();

    $analise = app(AnaliseService::class);
    $ordens = app(OrdemServicoService::class);

    // 2 aprovadas (uma concluída), 1 reprovada
    $o1 = Ocorrencia::factory()->criticidade(Criticidade::Critica)->create(['area_id' => $this->equipamento->area_id, 'equipamento_id' => $this->equipamento->id, 'titulo' => 'Vazamento crítico']);
    $o2 = Ocorrencia::factory()->criticidade(Criticidade::Alta)->create(['area_id' => $this->equipamento->area_id, 'equipamento_id' => $this->equipamento->id, 'titulo' => 'Ruído anormal']);
    $o3 = Ocorrencia::factory()->create(['titulo' => 'Foto ruim']);

    $this->os = $analise->aprovar($o1, $this->supervisor);
    $analise->aprovar($o2, $this->supervisor);
    $analise->reprovar($o3, $this->supervisor, MotivoReprovacao::factory()->create(['descricao' => 'Foto ilegível']));

    $this->actingAs($this->gerente);
    $ordens->transicionar($this->os, StatusOS::Programada, $this->gerente, ['equipe_id' => $this->equipe->id]);
    $ordens->transicionar($this->os, StatusOS::EmExecucao, $this->gerente);
    $ordens->transicionar($this->os, StatusOS::Concluida, $this->gerente, ['observacao_conclusao' => 'Trocada vedação'], [UploadedFile::fake()->image('d.jpg')]);

    $this->filtros = ['data_inicio' => now()->subMonth()->format('Y-m-d'), 'data_fim' => now()->format('Y-m-d')];
});

it('exibe a tela de relatórios', function () {
    $this->get(route('relatorios.index'))->assertOk()->assertSee('Ranking de equipamentos');
});

it('gera a prévia HTML de todos os relatórios', function (string $tipo) {
    $this->get(route('relatorios.show', ['tipo' => $tipo, ...$this->filtros]))->assertOk();
})->with(array_keys(RelatorioService::TIPOS));

it('gera o PDF de todos os relatórios', function (string $tipo) {
    $resp = $this->get(route('relatorios.pdf', ['tipo' => $tipo, ...$this->filtros]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(substr($resp->getContent(), 0, 4))->toBe('%PDF');
})->with(array_keys(RelatorioService::TIPOS));

it('mostra os dados corretos em cada relatório', function () {
    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas', ...$this->filtros]))
        ->assertSee('Vazamento crítico')->assertSee('Ruído anormal')->assertSee($this->supervisor->name)->assertDontSee('Foto ruim');

    $this->get(route('relatorios.show', ['tipo' => 'reprovadas', ...$this->filtros]))
        ->assertSee('Foto ruim')->assertSee('Foto ilegível');

    $this->get(route('relatorios.show', ['tipo' => 'os-concluidas', ...$this->filtros]))
        ->assertSee($this->os->numero)->assertSee('No prazo')->assertDontSee('Ruído anormal');

    $this->get(route('relatorios.show', ['tipo' => 'ranking-equipamentos', ...$this->filtros]))
        ->assertSee('BOMBA-01');

    $this->get(route('relatorios.show', ['tipo' => 'indicadores', ...$this->filtros]))
        ->assertSee('Tempo médio de aprovação')->assertSee('Total');
});

it('aplica filtros de criticidade e equipe', function () {
    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas', ...$this->filtros, 'criticidade' => 'alta']))
        ->assertSee('Ruído anormal')->assertDontSee('Vazamento crítico');

    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas', ...$this->filtros, 'equipe_id' => $this->equipe->id]))
        ->assertSee('Vazamento crítico')->assertDontSee('Ruído anormal');
});

it('exige período e limita a 12 meses', function () {
    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas']))->assertSessionHasErrors(['data_inicio', 'data_fim']);

    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas', 'data_inicio' => '2024-01-01', 'data_fim' => '2025-06-01']))
        ->assertSessionHasErrors('data_fim');

    $this->get(route('relatorios.show', ['tipo' => 'os-aprovadas', 'data_inicio' => '2025-06-01', 'data_fim' => '2025-01-01']))
        ->assertSessionHasErrors('data_fim');
});

it('tipo inexistente retorna 404', function () {
    $this->get(route('relatorios.show', ['tipo' => 'nao-existe', ...$this->filtros]))->assertNotFound();
});

it('gera a ficha da OS em PDF com as fotos embutidas', function () {
    $resp = $this->get(route('relatorios.ficha-os', $this->os))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect(substr($resp->getContent(), 0, 4))->toBe('%PDF')
        ->and(strlen($resp->getContent()))->toBeGreaterThan(5000);
});

it('libera relatórios para supervisor e master e bloqueia técnico', function () {
    $this->actingAs($this->supervisor)->get(route('relatorios.index'))->assertOk();
    $this->actingAs(User::factory()->master()->create())->get(route('relatorios.index'))->assertOk();
    $this->actingAs(User::factory()->tecnico()->create())->get(route('relatorios.index'))->assertForbidden();
    $this->get(route('relatorios.ficha-os', $this->os))->assertForbidden();
});
