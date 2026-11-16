<?php

use App\Enums\Criticidade;
use App\Enums\StatusOcorrencia;
use App\Enums\TipoFoto;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Equipamento;
use App\Models\Foto;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Models\User;
use App\Notifications\NovaOcorrencia;
use App\Notifications\OcorrenciaReenviada;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    $this->tecnico = User::factory()->tecnico()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->gerente = User::factory()->gerente()->create();
    $this->area = Area::factory()->create();
    $this->equipamento = Equipamento::factory()->for($this->area)->create();
    $this->categoria = Categoria::factory()->create();
});

function dadosOcorrencia(array $extra = []): array
{
    return array_merge([
        'area_id' => test()->area->id,
        'equipamento_id' => test()->equipamento->id,
        'categoria_id' => test()->categoria->id,
        'titulo' => 'Vazamento de óleo na bomba',
        'descricao' => 'Vazamento constante de óleo pela vedação do eixo, formando poça no piso.',
        'local' => 'Lado norte',
        'criticidade_sugerida' => 'alta',
        'risco_pessoas' => '1',
        'equipamento_parado' => '0',
        'fotos' => [UploadedFile::fake()->image('foto1.jpg', 3000, 2000)],
    ], $extra);
}

it('registra ocorrência com fotos comprimidas e número gerado', function () {
    $this->actingAs($this->tecnico)
        ->post(route('ocorrencias.store'), dadosOcorrencia([
            'fotos' => [
                UploadedFile::fake()->image('a.jpg', 3000, 2000),
                UploadedFile::fake()->image('b.png', 800, 600),
            ],
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $o = Ocorrencia::firstOrFail();
    expect($o->numero)->toBe(sprintf('OC-%s-%06d', now()->year, $o->id))
        ->and($o->status)->toBe(StatusOcorrencia::Pendente)
        ->and($o->criticidade)->toBe(Criticidade::Alta)
        ->and($o->risco_pessoas)->toBeTrue()
        ->and($o->fotos)->toHaveCount(2);

    $foto = $o->fotos->first();
    expect($foto->tipo)->toBe(TipoFoto::Antes)
        ->and($foto->path)->toStartWith('ocorrencias/'.now()->format('Y/m').'/')
        ->and($foto->path)->toEndWith('.jpg');

    Storage::disk('local')->assertExists($foto->path);
    [$largura, $altura] = getimagesizefromstring(Storage::disk('local')->get($foto->path));
    expect($largura)->toBe(1600)->and($altura)->toBeLessThanOrEqual(1600);
});

it('grava equipamento nulo para "Estrutura / Não se aplica"', function () {
    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia(['equipamento_id' => 0]))
        ->assertSessionHasNoErrors();

    expect(Ocorrencia::first()->equipamento_id)->toBeNull();
});

it('rejeita equipamento de outra área', function () {
    $outro = Equipamento::factory()->create();

    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia(['equipamento_id' => $outro->id]))
        ->assertSessionHasErrors('equipamento_id');
});

it('valida descrição mínima, foto obrigatória e máximo de 5 fotos', function () {
    $this->actingAs($this->tecnico);

    $this->post(route('ocorrencias.store'), dadosOcorrencia(['descricao' => 'curta', 'fotos' => []]))
        ->assertSessionHasErrors(['descricao', 'fotos']);

    $seis = array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg"), range(1, 6));
    $this->post(route('ocorrencias.store'), dadosOcorrencia(['fotos' => $seis]))->assertSessionHasErrors('fotos');

    expect(Ocorrencia::count())->toBe(0);
});

it('rejeita arquivo que não é imagem e foto acima de 10MB', function () {
    $this->actingAs($this->tecnico);

    $this->post(route('ocorrencias.store'), dadosOcorrencia(['fotos' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')]]))
        ->assertSessionHasErrors('fotos.0');

    $this->post(route('ocorrencias.store'), dadosOcorrencia(['fotos' => [UploadedFile::fake()->image('g.jpg')->size(11000)]]))
        ->assertSessionHasErrors('fotos.0');
});

it('notifica supervisores e, se crítica, também gerentes', function () {
    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia(['criticidade_sugerida' => 'media']));
    Notification::assertSentTo($this->supervisor, NovaOcorrencia::class);
    Notification::assertNotSentTo($this->gerente, NovaOcorrencia::class);

    $this->post(route('ocorrencias.store'), dadosOcorrencia(['criticidade_sugerida' => 'critica']));
    Notification::assertSentTo($this->gerente, NovaOcorrencia::class);
});

it('lista apenas as minhas ocorrências com filtro por status', function () {
    Ocorrencia::factory()->for($this->tecnico)->create(['titulo' => 'Minha pendente']);
    Ocorrencia::factory()->for($this->tecnico)->status(StatusOcorrencia::Reprovada)->create(['titulo' => 'Minha reprovada']);
    Ocorrencia::factory()->create(['titulo' => 'De outro técnico']);

    $this->actingAs($this->tecnico)->get(route('ocorrencias.index'))
        ->assertOk()->assertSee('Minha pendente')->assertSee('Minha reprovada')->assertDontSee('De outro técnico');

    $this->get(route('ocorrencias.index', ['status' => 'reprovada']))
        ->assertSee('Minha reprovada')->assertDontSee('Minha pendente');
});

it('exibe o formulário e o detalhe', function () {
    $o = Ocorrencia::factory()->for($this->tecnico)->create();

    $this->actingAs($this->tecnico)->get(route('ocorrencias.create'))->assertOk()->assertSee('capture="environment"', false);
    $this->get(route('ocorrencias.show', $o))->assertOk()->assertSee($o->numero);
});

it('impede outro técnico de ver a ocorrência e suas fotos', function () {
    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia());
    $o = Ocorrencia::first();
    $foto = $o->fotos->first();

    $intruso = User::factory()->tecnico()->create();
    $this->actingAs($intruso)->get(route('ocorrencias.show', $o))->assertForbidden();
    $this->actingAs($intruso)->get(route('fotos.show', $foto))->assertForbidden();

    $this->actingAs($this->tecnico)->get(route('fotos.show', $foto))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->actingAs($this->supervisor)->get(route('fotos.show', $foto))->assertOk();
});

it('visitante não acessa foto', function () {
    $o = Ocorrencia::factory()->create();
    $foto = Foto::create(['ocorrencia_id' => $o->id, 'tipo' => 'antes', 'path' => 'x.jpg', 'tamanho' => 1, 'user_id' => $o->user_id]);

    $this->get(route('fotos.show', $foto))->assertRedirect('/login');
});

it('supervisor e gerente não registram ocorrência', function () {
    $this->actingAs($this->supervisor)->get(route('ocorrencias.create'))->assertForbidden();
    $this->actingAs($this->gerente)->post(route('ocorrencias.store'), dadosOcorrencia())->assertForbidden();
});

it('técnico dono cancela ocorrência pendente', function () {
    $o = Ocorrencia::factory()->for($this->tecnico)->create();

    $this->actingAs($this->tecnico)->patch(route('ocorrencias.cancelar', $o))->assertRedirect();
    expect($o->fresh()->status)->toBe(StatusOcorrencia::Cancelada);
});

it('não cancela ocorrência de outro nem já aprovada', function () {
    $alheia = Ocorrencia::factory()->create();
    $aprovada = Ocorrencia::factory()->for($this->tecnico)->status(StatusOcorrencia::Aprovada)->create();

    $this->actingAs($this->tecnico)->patch(route('ocorrencias.cancelar', $alheia))->assertForbidden();
    $this->actingAs($this->tecnico)->patch(route('ocorrencias.cancelar', $aprovada))->assertForbidden();
});

it('corrige e reenvia ocorrência reprovada', function () {
    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia());
    $o = Ocorrencia::first();
    $o->update(['status' => StatusOcorrencia::Reprovada]);
    $o->analises()->create([
        'user_id' => $this->supervisor->id, 'acao' => 'reprovada',
        'motivo_reprovacao_id' => MotivoReprovacao::factory()->create()->id,
    ]);
    $fotoAntiga = $o->fotos->first();

    $this->get(route('ocorrencias.edit', $o))->assertOk()->assertSee('Corrigir e reenviar');

    $this->put(route('ocorrencias.update', $o), dadosOcorrencia([
        'descricao' => 'Descrição corrigida com muito mais detalhes sobre o vazamento observado.',
        'fotos' => [UploadedFile::fake()->image('nova.jpg')],
        'remover_fotos' => [$fotoAntiga->id],
    ]))->assertRedirect(route('ocorrencias.show', $o))->assertSessionHasNoErrors();

    $o->refresh();
    expect($o->status)->toBe(StatusOcorrencia::Pendente)
        ->and($o->reenvios)->toBe(1)
        ->and($o->fotos()->count())->toBe(1)
        ->and($o->fotos()->first()->id)->not->toBe($fotoAntiga->id);

    Storage::disk('local')->assertMissing($fotoAntiga->path);
    Notification::assertSentTo($this->supervisor, OcorrenciaReenviada::class);
});

it('não permite editar ocorrência pendente', function () {
    $o = Ocorrencia::factory()->for($this->tecnico)->create();

    $this->actingAs($this->tecnico)->get(route('ocorrencias.edit', $o))->assertForbidden();
    $this->put(route('ocorrencias.update', $o), dadosOcorrencia())->assertForbidden();
});

it('reenvio exige pelo menos uma foto restante', function () {
    $this->actingAs($this->tecnico)->post(route('ocorrencias.store'), dadosOcorrencia());
    $o = Ocorrencia::first();
    $o->update(['status' => StatusOcorrencia::Reprovada]);

    $this->put(route('ocorrencias.update', $o), dadosOcorrencia([
        'fotos' => [],
        'remover_fotos' => [$o->fotos->first()->id],
    ]))->assertSessionHasErrors('fotos');
});
