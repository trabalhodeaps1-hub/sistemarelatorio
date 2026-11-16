<?php

use App\Enums\StatusOcorrencia;
use App\Enums\StatusOS;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Equipamento;
use App\Models\Equipe;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Models\SlaPrazo;
use App\Models\User;
use App\Notifications\NovaOcorrencia;
use App\Notifications\OcorrenciaAprovada;
use App\Notifications\OcorrenciaReenviada;
use App\Notifications\OcorrenciaReprovada;
use App\Notifications\OSConcluida;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);

    $this->tecnico = User::where('email', 'tecnico@autorelatorio.local')->first() ?? User::factory()->tecnico()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->gerente = User::factory()->gerente()->create();
    $this->equipamento = Equipamento::where('tag', 'PMP-TC-01')->firstOrFail();
});

function registrar(): Ocorrencia
{
    test()->actingAs(test()->tecnico)->post(route('ocorrencias.store'), [
        'area_id' => test()->equipamento->area_id,
        'equipamento_id' => test()->equipamento->id,
        'categoria_id' => Categoria::where('nome', 'Defeito em equipamento')->value('id'),
        'titulo' => 'Rolete travado na TC-01',
        'descricao' => 'Rolete de carga travado gerando aquecimento e desgaste da correia transportadora.',
        'local' => 'Trecho 3, lado do motor',
        'criticidade_sugerida' => 'alta',
        'equipamento_parado' => '1',
        'fotos' => [UploadedFile::fake()->image('antes.jpg', 2400, 1800)],
    ])->assertSessionHasNoErrors();

    return Ocorrencia::latest('id')->firstOrFail();
}

it('executa ocorrência → aprovação → OS → conclusão', function () {
    $o = registrar();
    expect($this->supervisor->notifications()->where('type', NovaOcorrencia::class)->count())->toBe(1);

    // Supervisor aprova
    $this->actingAs($this->supervisor)->get(route('analises.index'))->assertSee($o->numero);
    $this->post(route('analises.aprovar', $o), ['criticidade' => 'alta'])->assertSessionHasNoErrors();
    $os = $o->fresh()->ordemServico;
    expect($os->status)->toBe(StatusOS::Aberta);
    expect($this->gerente->notifications()->where('type', OcorrenciaAprovada::class)->count())->toBe(1);
    expect($this->tecnico->notifications()->where('type', OcorrenciaAprovada::class)->count())->toBe(1);

    // Gerente executa
    $this->actingAs($this->gerente);
    $equipe = Equipe::where('nome', 'Mecânica')->firstOrFail();
    $this->post(route('ordens-servico.transicionar', $os), ['status' => 'programada', 'equipe_id' => $equipe->id])->assertSessionHasNoErrors();
    $this->post(route('ordens-servico.transicionar', $os), ['status' => 'em_execucao'])->assertSessionHasNoErrors();
    $this->post(route('ordens-servico.transicionar', $os), [
        'status' => 'concluida',
        'observacao_conclusao' => 'Substituído o rolete e verificado o alinhamento da correia.',
        'fotos' => [UploadedFile::fake()->image('depois.jpg', 1200, 900)],
    ])->assertSessionHasNoErrors();

    $os->refresh();
    expect($os->status)->toBe(StatusOS::Concluida)
        ->and($os->historico()->pluck('status_novo')->map->value->all())->toBe(['aberta', 'programada', 'em_execucao', 'concluida']);
    expect($this->tecnico->notifications()->where('type', OSConcluida::class)->count())->toBe(1);

    // Técnico vê o resultado com fotos de antes e depois
    $this->actingAs($this->tecnico)->get(route('ocorrencias.show', $o))
        ->assertOk()->assertSee($os->numero)->assertSee('Fotos depois do reparo');

    // Ficha da OS
    $this->actingAs($this->gerente)->get(route('relatorios.ficha-os', $os))->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('executa reprovação → reenvio → nova aprovação', function () {
    $o = registrar();
    $motivo = MotivoReprovacao::where('descricao', 'Localização insuficiente')->firstOrFail();

    $this->actingAs($this->supervisor)->post(route('analises.reprovar', $o), [
        'motivo_reprovacao_id' => $motivo->id, 'comentario' => 'Informe o número do rolete.',
    ])->assertSessionHasNoErrors();
    expect($o->fresh()->status)->toBe(StatusOcorrencia::Reprovada);
    expect($this->tecnico->notifications()->where('type', OcorrenciaReprovada::class)->count())->toBe(1);

    // Técnico corrige e reenvia
    $this->actingAs($this->tecnico)->get(route('ocorrencias.edit', $o))->assertOk()->assertSee('Informe o número do rolete.');
    $this->put(route('ocorrencias.update', $o), [
        'area_id' => $this->equipamento->area_id,
        'equipamento_id' => $this->equipamento->id,
        'categoria_id' => $o->categoria_id,
        'titulo' => 'Rolete 37 travado na TC-01',
        'descricao' => 'Rolete de carga nº 37 travado gerando aquecimento e desgaste da correia.',
        'local' => 'Trecho 3, rolete 37',
        'criticidade_sugerida' => 'alta',
    ])->assertSessionHasNoErrors();

    $o->refresh();
    expect($o->status)->toBe(StatusOcorrencia::Pendente)->and($o->reenvios)->toBe(1);
    expect($this->supervisor->notifications()->where('type', OcorrenciaReenviada::class)->count())->toBe(1);

    // Nova análise: aprovada
    $this->actingAs($this->supervisor)->post(route('analises.aprovar', $o), ['criticidade' => 'alta'])->assertSessionHasNoErrors();

    $o->refresh();
    expect($o->status)->toBe(StatusOcorrencia::Aprovada)
        ->and($o->analises()->count())->toBe(2)
        ->and($o->ordemServico)->not->toBeNull();
});

it('seeders são idempotentes', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Area::count())->toBe(10)
        ->and(Equipamento::count())->toBe(40)
        ->and(Categoria::count())->toBe(8)
        ->and(MotivoReprovacao::count())->toBe(10)
        ->and(Equipe::count())->toBe(6)
        ->and(SlaPrazo::count())->toBe(4);
});
