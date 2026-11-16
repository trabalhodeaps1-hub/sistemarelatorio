<?php

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Equipamento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('registra criação, alteração e exclusão com as mudanças', function () {
    $master = User::factory()->master()->create();
    $this->actingAs($master);

    $area = Area::factory()->create(['nome' => 'Original']);
    $area->update(['nome' => 'Alterada']);
    $area->delete();

    $logs = ActivityLog::where('model_type', Area::class)->where('model_id', $area->id)->orderBy('id')->get();

    expect($logs->pluck('acao')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($logs[1]->dados['antes']['nome'])->toBe('Original')
        ->and($logs[1]->dados['depois']['nome'])->toBe('Alterada')
        ->and($logs[1]->user_id)->toBe($master->id);
});

it('nunca grava a senha no log', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'outraSenha123']);

    $log = ActivityLog::where('model_type', User::class)->where('model_id', $user->id)->where('acao', 'updated')->first();

    expect($log->dados['depois']['password'])->toBe('[alterada]')
        ->and(json_encode(ActivityLog::all()->pluck('dados')))->not->toContain('$2y$');
});

it('tela de auditoria é exclusiva do master e filtra', function () {
    $master = User::factory()->master()->create();
    $this->actingAs($master);
    Categoria::factory()->create(['nome' => 'Cat auditada']);

    $this->get(route('logs.index'))->assertOk()->assertSee('Categoria');
    $this->get(route('logs.index', ['acao' => 'deleted']))->assertOk()->assertSee('Nenhum registro');
    $this->get(route('logs.index', ['modelo' => 'Categoria', 'acao' => 'created']))->assertSee('Cat auditada');

    $this->actingAs(User::factory()->gerente()->create())->get(route('logs.index'))->assertForbidden();
});

it('todas as rotas da aplicação exigem autenticação, exceto login e health', function () {
    $publicas = ['login', 'login.store'];

    $semAuth = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => str_starts_with($r->getActionName(), 'App\\'))
        ->reject(fn ($r) => in_array($r->getName(), $publicas, true))
        ->reject(fn ($r) => in_array('auth', $r->gatherMiddleware(), true))
        ->map(fn ($r) => $r->uri())
        ->values()
        ->all();

    expect($semAuth)->toBe([]);
});

it('não expõe a rota de arquivos do disco local', function () {
    expect(Route::has('storage.local'))->toBeFalse();
});

it('rejeita arquivo disfarçado de imagem (mime real)', function () {
    Storage::fake('local');
    Notification::fake();
    $equip = Equipamento::factory()->create();

    $falsa = UploadedFile::fake()->createWithContent('foto.jpg', "%PDF-1.4\n%falso pdf com extensão jpg\n");

    $this->actingAs(User::factory()->tecnico()->create())->post(route('ocorrencias.store'), [
        'area_id' => $equip->area_id, 'equipamento_id' => $equip->id, 'categoria_id' => Categoria::factory()->create()->id,
        'titulo' => 'Teste mime', 'descricao' => str_repeat('Descrição longa o suficiente. ', 3),
        'criticidade_sugerida' => 'baixa', 'fotos' => [$falsa],
    ])->assertSessionHasErrors('fotos.0');
});
