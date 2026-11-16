<?php

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use App\Enums\TipoFoto;
use App\Models\Equipe;
use App\Models\Ocorrencia;
use App\Models\OrdemServico;
use App\Models\User;
use App\Notifications\OSConcluida;
use App\Services\AnaliseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->gerente = User::factory()->gerente()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->tecnico = User::factory()->tecnico()->create();
    $this->equipe = Equipe::factory()->create();
});

function novaOS(array $estado = []): OrdemServico
{
    $o = Ocorrencia::factory()->for(test()->tecnico)->criticidade(Criticidade::Alta)->create();
    $os = app(AnaliseService::class)->aprovar($o, test()->supervisor);
    if ($estado) {
        $os->update($estado);
    }

    return $os->fresh();
}

function transicao(OrdemServico $os, StatusOS $status, array $dados = [])
{
    return test()->actingAs(test()->gerente)
        ->post(route('ordens-servico.transicionar', $os), ['status' => $status->value, ...$dados]);
}

it('lista OS com filtros e badges de prazo', function () {
    $atrasada = novaOS(['prazo_previsto' => now()->subHour()]);
    $vencendo = novaOS(['prazo_previsto' => now()->addHours(5)]);
    $folgada = novaOS(['prazo_previsto' => now()->addDays(5)]);

    $this->actingAs($this->gerente)->get(route('ordens-servico.index'))
        ->assertOk()->assertSee($atrasada->numero)->assertSee('Atrasada')->assertSee('Vence em 24h');

    $this->get(route('ordens-servico.index', ['situacao' => 'atrasadas']))
        ->assertSee($atrasada->numero)->assertDontSee($folgada->numero)->assertDontSee($vencendo->numero);

    $this->get(route('ordens-servico.index', ['status' => 'programada']))->assertDontSee($atrasada->numero);
});

it('mostra no detalhe apenas os botões das transições válidas', function () {
    $os = novaOS();

    $this->actingAs($this->gerente)->get(route('ordens-servico.show', $os))
        ->assertOk()
        ->assertSee('modal-programada', false)
        ->assertSee('modal-cancelada', false)
        ->assertDontSee('modal-concluida', false)
        ->assertDontSee('modal-em_execucao', false);
});

it('programa exigindo equipe e permite ajustar o prazo', function () {
    $os = novaOS();

    transicao($os, StatusOS::Programada)->assertSessionHasErrors('equipe_id');

    transicao($os, StatusOS::Programada, ['equipe_id' => $this->equipe->id, 'prazo_previsto' => '2030-01-10T15:30'])
        ->assertRedirect(route('ordens-servico.show', $os));

    $os->refresh();
    expect($os->status)->toBe(StatusOS::Programada)
        ->and($os->equipe_id)->toBe($this->equipe->id)
        ->and($os->prazo_previsto->format('Y-m-d H:i'))->toBe('2030-01-10 15:30');
});

it('executa o fluxo completo até a conclusão com fotos de antes e depois', function () {
    $os = novaOS();

    transicao($os, StatusOS::Programada, ['equipe_id' => $this->equipe->id]);
    transicao($os, StatusOS::EmExecucao);
    expect($os->fresh()->iniciada_em)->not->toBeNull();

    transicao($os, StatusOS::Pausada, ['comentario' => 'Aguardando peça']);
    transicao($os, StatusOS::EmExecucao);

    // Sem foto e sem observação: bloqueia
    transicao($os, StatusOS::Concluida)->assertSessionHasErrors('observacao_conclusao');
    transicao($os, StatusOS::Concluida, ['observacao_conclusao' => 'Trocada a vedação'])->assertSessionHasErrors('fotos');
    expect($os->fresh()->status)->toBe(StatusOS::EmExecucao);

    transicao($os, StatusOS::Concluida, [
        'observacao_conclusao' => 'Trocada a vedação do eixo e limpo o piso.',
        'fotos' => [UploadedFile::fake()->image('depois.jpg', 2000, 1500)],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $os->refresh();
    expect($os->status)->toBe(StatusOS::Concluida)
        ->and($os->concluida_em)->not->toBeNull()
        ->and($os->observacao_conclusao)->toContain('vedação')
        ->and($os->ocorrencia->fotos()->where('tipo', TipoFoto::Depois->value)->count())->toBe(1)
        ->and($os->historico()->count())->toBe(6); // aberta + 5 transições

    Notification::assertSentTo($this->tecnico, OSConcluida::class);

    $this->actingAs($this->gerente)->get(route('ordens-servico.show', $os))
        ->assertOk()->assertSee('Antes')->assertSee('Depois')->assertSee('Trocada a vedação');
});

it('cancela exigindo justificativa', function () {
    $os = novaOS();

    transicao($os, StatusOS::Cancelada)->assertSessionHasErrors('comentario');
    transicao($os, StatusOS::Cancelada, ['comentario' => 'Duplicada da OS anterior'])->assertSessionHasNoErrors();

    $os->refresh();
    expect($os->status)->toBe(StatusOS::Cancelada)
        ->and($os->cancelada_em)->not->toBeNull()
        ->and($os->historico->last()->comentario)->toBe('Duplicada da OS anterior');
});

it('retorna 422 para transições inválidas', function (StatusOS $de, StatusOS $para) {
    $os = novaOS(['status' => $de]);

    transicao($os, $para, ['observacao_conclusao' => 'x', 'comentario' => 'x', 'equipe_id' => $this->equipe->id])
        ->assertStatus(422);

    expect($os->fresh()->status)->toBe($de);
})->with([
    'aberta → concluída' => [StatusOS::Aberta, StatusOS::Concluida],
    'aberta → em execução' => [StatusOS::Aberta, StatusOS::EmExecucao],
    'programada → aberta' => [StatusOS::Programada, StatusOS::Aberta],
    'em execução → cancelada' => [StatusOS::EmExecucao, StatusOS::Cancelada],
    'concluída → em execução' => [StatusOS::Concluida, StatusOS::EmExecucao],
    'cancelada → programada' => [StatusOS::Cancelada, StatusOS::Programada],
]);

it('apenas gerente e master gerenciam OS', function () {
    $os = novaOS();

    $this->actingAs($this->tecnico)->get(route('ordens-servico.index'))->assertForbidden();
    $this->actingAs($this->supervisor)->get(route('ordens-servico.show', $os))->assertForbidden();
    $this->actingAs($this->supervisor)->post(route('ordens-servico.transicionar', $os), ['status' => 'cancelada', 'comentario' => 'x'])->assertForbidden();

    $this->actingAs(User::factory()->master()->create())->get(route('ordens-servico.show', $os))->assertOk();
});

it('a máquina de estados do enum está conforme o roadmap', function () {
    expect(StatusOS::Aberta->transicoesPermitidas())->toBe([StatusOS::Programada, StatusOS::Cancelada])
        ->and(StatusOS::Programada->transicoesPermitidas())->toBe([StatusOS::EmExecucao, StatusOS::Pausada, StatusOS::Cancelada])
        ->and(StatusOS::EmExecucao->transicoesPermitidas())->toBe([StatusOS::Pausada, StatusOS::Concluida])
        ->and(StatusOS::Pausada->transicoesPermitidas())->toBe([StatusOS::EmExecucao, StatusOS::Cancelada])
        ->and(StatusOS::Concluida->transicoesPermitidas())->toBe([])
        ->and(StatusOS::Cancelada->transicoesPermitidas())->toBe([]);
});
