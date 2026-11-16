<?php

use App\Enums\Criticidade;
use App\Enums\Role;
use App\Enums\StatusOS;
use App\Models\Ocorrencia;
use App\Models\User;
use App\Notifications\NovaOcorrencia;
use App\Notifications\OSAtrasada;
use App\Services\AnaliseService;
use Illuminate\Console\Scheduling\Schedule;

it('mostra contador e dropdown do sino com a notificação', function () {
    $supervisor = User::factory()->supervisor()->create();
    $o = Ocorrencia::factory()->criticidade(Criticidade::Critica)->create();
    $supervisor->notify(new NovaOcorrencia($o));

    $this->actingAs($supervisor)->get('/')
        ->assertOk()
        ->assertSee('Nova ocorrência '.$o->numero);

    $this->getJson(route('notificacoes.contador'))->assertJson(['nao_lidas' => 1]);

    $n = $supervisor->notifications()->first();
    expect($n->data)->toHaveKeys(['titulo', 'mensagem', 'url', 'nivel'])
        ->and($n->data['nivel'])->toBe('critico');
});

it('abre a notificação marcando como lida e redirecionando', function () {
    $supervisor = User::factory()->supervisor()->create();
    $o = Ocorrencia::factory()->create();
    $supervisor->notify(new NovaOcorrencia($o));
    $n = $supervisor->notifications()->first();

    $this->actingAs($supervisor)->get(route('notificacoes.abrir', $n->id))
        ->assertRedirect(route('analises.show', $o, false));

    expect($n->fresh()->read_at)->not->toBeNull();
});

it('marca todas como lidas e lista', function () {
    $user = User::factory()->supervisor()->create();
    Ocorrencia::factory()->count(3)->create()->each(fn ($o) => $user->notify(new NovaOcorrencia($o)));

    $this->actingAs($user)->get(route('notificacoes.index'))->assertOk()->assertSee('Nova ocorrência');
    $this->post(route('notificacoes.ler-todas'))->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('não acessa notificação de outro usuário', function () {
    $dono = User::factory()->supervisor()->create();
    $dono->notify(new NovaOcorrencia(Ocorrencia::factory()->create()));

    $this->actingAs(User::factory()->create())
        ->get(route('notificacoes.abrir', $dono->notifications()->first()->id))
        ->assertNotFound();
});

it('notifica OS atrasada uma única vez por OS', function () {
    $gerente = User::factory()->gerente()->create();
    $o = Ocorrencia::factory()->criticidade(Criticidade::Alta)->create();
    $os = app(AnaliseService::class)->aprovar($o, User::factory()->supervisor()->create());
    $os->update(['prazo_previsto' => now()->subHour()]);
    $gerente->notifications()->delete();

    $this->artisan('os:verificar-atrasos')->assertSuccessful();
    $this->artisan('os:verificar-atrasos')->assertSuccessful();

    expect($gerente->notifications()->where('type', OSAtrasada::class)->count())->toBe(1)
        ->and($os->fresh()->atraso_notificado_em)->not->toBeNull();
});

it('não notifica OS concluída ou dentro do prazo', function () {
    $gerente = User::factory()->gerente()->create();
    $sup = User::factory()->supervisor()->create();
    $os1 = app(AnaliseService::class)->aprovar(Ocorrencia::factory()->create(), $sup);
    $os2 = app(AnaliseService::class)->aprovar(Ocorrencia::factory()->create(), $sup);
    $os1->update(['prazo_previsto' => now()->addDay()]);
    $os2->update(['prazo_previsto' => now()->subDay(), 'status' => StatusOS::Concluida]);

    $this->artisan('os:verificar-atrasos');

    expect($gerente->notifications()->where('type', OSAtrasada::class)->count())->toBe(0);
});

it('agenda a verificação de atrasos de hora em hora', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'os:verificar-atrasos'));

    expect($evento)->not->toBeNull()->and($evento->expression)->toBe('0 * * * *');
});

it('exibe o dashboard de cada perfil com seus cards', function (Role $role, array $textos) {
    $sup = User::factory()->supervisor()->create();
    app(AnaliseService::class)->aprovar(Ocorrencia::factory()->create(), $sup);

    $resp = $this->actingAs(User::factory()->role($role)->create())->get('/')->assertOk();
    foreach ($textos as $t) {
        $resp->assertSee($t);
    }
})->with([
    'técnico' => [Role::Tecnico, ['Pendentes de análise', 'Reprovadas para corrigir', 'Aprovadas']],
    'supervisor' => [Role::Supervisor, ['Pendentes', 'Críticas pendentes', 'Analisadas hoje', 'Ocorrências por mês']],
    'gerente' => [Role::Gerente, ['Abertas', 'Em execução', 'Atrasadas', 'Vencem em 24h', 'Concluídas no mês', 'Top 5 equipamentos']],
    'master' => [Role::Master, ['Usuários ativos', 'OS em andamento', 'OS atrasadas']],
]);
