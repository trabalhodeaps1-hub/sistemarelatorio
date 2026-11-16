<?php

use App\Enums\Criticidade;
use App\Enums\StatusOcorrencia;
use App\Enums\StatusOS;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Models\SlaPrazo;
use App\Models\User;
use App\Notifications\OcorrenciaAprovada;
use App\Notifications\OcorrenciaReprovada;
use App\Services\AnaliseService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->gerente = User::factory()->gerente()->create();
    $this->tecnico = User::factory()->tecnico()->create();
    SlaPrazo::create(['criticidade' => 'critica', 'horas' => 24]);
    SlaPrazo::create(['criticidade' => 'alta', 'horas' => 72]);
});

it('ordena a fila por criticidade decrescente e data crescente', function () {
    $this->travel(-3)->hours();
    $mediaAntiga = Ocorrencia::factory()->criticidade(Criticidade::Media)->create(['titulo' => 'Media antiga']);
    $this->travelBack();
    $this->travel(-2)->hours();
    $criticaAntiga = Ocorrencia::factory()->criticidade(Criticidade::Critica)->create(['titulo' => 'Critica antiga']);
    $this->travelBack();
    $criticaNova = Ocorrencia::factory()->criticidade(Criticidade::Critica)->create(['titulo' => 'Critica nova']);
    Ocorrencia::factory()->status(StatusOcorrencia::Aprovada)->create(['titulo' => 'Ja aprovada']);

    $this->actingAs($this->supervisor)->get(route('analises.index'))
        ->assertOk()
        ->assertSeeInOrder(['Critica antiga', 'Critica nova', 'Media antiga'])
        ->assertDontSee('Ja aprovada')
        ->assertSee('crítica(s) aguardando análise', false);
});

it('aprova gerando OS aberta com prazo do SLA e histórico', function () {
    $this->freezeTime();
    $o = Ocorrencia::factory()->for($this->tecnico)->criticidade(Criticidade::Media)->create();

    $this->actingAs($this->supervisor)->get(route('analises.show', $o))->assertOk()->assertSee('Aprovar e gerar OS');

    $this->post(route('analises.aprovar', $o), ['criticidade' => 'critica', 'comentario' => 'Urgente'])
        ->assertRedirect(route('analises.index'))
        ->assertSessionHasNoErrors();

    $o->refresh();
    $os = $o->ordemServico;

    expect($o->status)->toBe(StatusOcorrencia::Aprovada)
        ->and($o->criticidade)->toBe(Criticidade::Critica)
        ->and($o->criticidade_sugerida)->toBe(Criticidade::Media)
        ->and($o->analises)->toHaveCount(1)
        ->and($o->analises->first()->acao)->toBe(StatusOcorrencia::Aprovada)
        ->and($os->status)->toBe(StatusOS::Aberta)
        ->and($os->numero)->toBe(sprintf('OS-%s-%06d', now()->year, $os->id))
        ->and($os->prazo_previsto->format('Y-m-d H:i'))->toBe(now()->addHours(24)->format('Y-m-d H:i'))
        ->and($os->historico)->toHaveCount(1)
        ->and($os->historico->first()->status_novo)->toBe(StatusOS::Aberta);

    Notification::assertSentTo($this->gerente, OcorrenciaAprovada::class, fn ($n) => $n->paraGerente);
    Notification::assertSentTo($this->tecnico, OcorrenciaAprovada::class, fn ($n) => ! $n->paraGerente);
});

it('reprova com motivo e devolve ao técnico', function () {
    $o = Ocorrencia::factory()->for($this->tecnico)->create();
    $motivo = MotivoReprovacao::factory()->create();

    $this->actingAs($this->supervisor)->post(route('analises.reprovar', $o), ['motivo_reprovacao_id' => $motivo->id])
        ->assertRedirect(route('analises.index'));

    $o->refresh();
    expect($o->status)->toBe(StatusOcorrencia::Reprovada)
        ->and($o->ordemServico)->toBeNull()
        ->and($o->analises->first()->motivo_reprovacao_id)->toBe($motivo->id);

    Notification::assertSentTo($this->tecnico, OcorrenciaReprovada::class);

    $this->actingAs($this->tecnico)->get(route('ocorrencias.show', $o))->assertSee($motivo->descricao);
});

it('exige motivo e, quando o motivo exige, comentário', function () {
    $o = Ocorrencia::factory()->create();
    $motivo = MotivoReprovacao::factory()->exigeComentario()->create();

    $this->actingAs($this->supervisor);
    $this->post(route('analises.reprovar', $o), [])->assertSessionHasErrors('motivo_reprovacao_id');
    $this->post(route('analises.reprovar', $o), ['motivo_reprovacao_id' => $motivo->id])->assertSessionHasErrors('comentario');
    $this->post(route('analises.reprovar', $o), ['motivo_reprovacao_id' => $motivo->id, 'comentario' => 'Não é falha'])->assertSessionHasNoErrors();

    expect($o->fresh()->status)->toBe(StatusOcorrencia::Reprovada);
});

it('não analisa ocorrência que não está pendente', function () {
    $o = Ocorrencia::factory()->status(StatusOcorrencia::Aprovada)->create();

    $this->actingAs($this->supervisor)->post(route('analises.aprovar', $o), ['criticidade' => 'alta'])->assertForbidden();
    $this->get(route('analises.show', $o))->assertRedirect(route('ocorrencias.show', $o));
});

it('impede análise duplicada (segunda aprovação falha)', function () {
    $o = Ocorrencia::factory()->criticidade(Criticidade::Alta)->create();
    $service = app(AnaliseService::class);

    $service->aprovar($o, $this->supervisor);

    expect(fn () => $service->aprovar($o, $this->supervisor))->toThrow(ValidationException::class);
    expect($o->fresh()->analises)->toHaveCount(1);
});

it('técnico e gerente não acessam a análise', function () {
    $o = Ocorrencia::factory()->create();

    $this->actingAs($this->tecnico)->get(route('analises.index'))->assertForbidden();
    $this->actingAs($this->gerente)->post(route('analises.aprovar', $o), ['criticidade' => 'alta'])->assertForbidden();
});

it('master pode analisar', function () {
    $o = Ocorrencia::factory()->criticidade(Criticidade::Alta)->create();

    $this->actingAs(User::factory()->master()->create())
        ->post(route('analises.aprovar', $o), ['criticidade' => 'alta'])
        ->assertRedirect();

    expect($o->fresh()->status)->toBe(StatusOcorrencia::Aprovada);
});
