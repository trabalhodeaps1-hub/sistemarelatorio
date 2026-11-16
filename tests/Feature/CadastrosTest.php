<?php

use App\Enums\Criticidade;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Equipamento;
use App\Models\Equipe;
use App\Models\MotivoReprovacao;
use App\Models\SlaPrazo;
use App\Models\User;

beforeEach(function () {
    $this->master = User::factory()->master()->create();
});

it('bloqueia cadastros para perfis que não são master', function (string $metodo) {
    $user = User::factory()->$metodo()->create();

    foreach (['usuarios', 'areas', 'equipamentos', 'categorias', 'motivos', 'equipes'] as $rota) {
        $this->actingAs($user)->get(route("$rota.index"))->assertForbidden();
    }
    $this->actingAs($user)->get(route('sla.index'))->assertForbidden();
    $this->actingAs($user)->post(route('areas.store'), ['codigo' => 'X', 'nome' => 'X'])->assertForbidden();
})->with(['tecnico', 'supervisor', 'gerente']);

it('master acessa todas as listagens e formulários', function () {
    $this->actingAs($this->master);

    foreach (['usuarios', 'areas', 'equipamentos', 'categorias', 'motivos', 'equipes'] as $rota) {
        $this->get(route("$rota.index"))->assertOk();
        $this->get(route("$rota.create"))->assertOk();
    }
    $this->get(route('sla.index'))->assertOk();
});

it('cria, edita e filtra áreas', function () {
    $this->actingAs($this->master)
        ->post(route('areas.store'), ['codigo' => 'tst', 'nome' => 'Área Teste', 'ativo' => '1'])
        ->assertRedirect(route('areas.index'));

    $area = Area::where('codigo', 'TST')->firstOrFail();

    $this->put(route('areas.update', $area), ['codigo' => 'TST', 'nome' => 'Renomeada', 'ativo' => '1'])
        ->assertRedirect();
    expect($area->fresh()->nome)->toBe('Renomeada');

    $this->get(route('areas.index', ['busca' => 'renom']))->assertSee('Renomeada');
    $this->get(route('areas.index', ['situacao' => 'inativos']))->assertDontSee('Renomeada');
});

it('valida campos obrigatórios e duplicidade em pt-BR', function () {
    Area::factory()->create(['codigo' => 'DUP']);

    $this->actingAs($this->master)
        ->post(route('areas.store'), ['codigo' => 'DUP', 'nome' => ''])
        ->assertSessionHasErrors([
            'codigo' => 'O campo código já está sendo utilizado.',
            'nome' => 'O campo nome é obrigatório.',
        ]);
});

it('não exclui área com equipamentos, apenas desativa', function () {
    $equipamento = Equipamento::factory()->create();
    $area = $equipamento->area;

    $this->actingAs($this->master)->delete(route('areas.destroy', $area))->assertSessionHasErrors('excluir');
    expect($area->fresh())->not->toBeNull();

    $this->patch(route('areas.alternar', $area))->assertRedirect();
    expect($area->fresh()->ativo)->toBeFalse();
});

it('exclui logicamente área sem vínculos (soft delete)', function () {
    $area = Area::factory()->create();

    $this->actingAs($this->master)->delete(route('areas.destroy', $area))->assertRedirect(route('areas.index'));

    expect(Area::find($area->id))->toBeNull()
        ->and(Area::withTrashed()->find($area->id))->not->toBeNull();
});

it('cria equipamento e filtra por área', function () {
    $a1 = Area::factory()->create();
    $a2 = Area::factory()->create();
    Equipamento::factory()->for($a2)->create(['nome' => 'Bomba da outra área']);

    $this->actingAs($this->master)->post(route('equipamentos.store'), [
        'area_id' => $a1->id, 'tag' => 'bmb-01', 'nome' => 'Bomba principal', 'criticidade' => 'alta', 'ativo' => '1',
    ])->assertRedirect(route('equipamentos.index'));

    expect(Equipamento::where('tag', 'BMB-01')->first()->criticidade)->toBe(Criticidade::Alta);

    $this->get(route('equipamentos.index', ['area_id' => $a1->id]))
        ->assertSee('Bomba principal')
        ->assertDontSee('Bomba da outra área');
});

it('rejeita criticidade inválida no equipamento', function () {
    $this->actingAs($this->master)->post(route('equipamentos.store'), [
        'area_id' => Area::factory()->create()->id, 'tag' => 'X1', 'nome' => 'X', 'criticidade' => 'urgentissima',
    ])->assertSessionHasErrors('criticidade');
});

it('endpoint JSON devolve só equipamentos ativos da área', function () {
    $area = Area::factory()->create();
    Equipamento::factory()->for($area)->create(['tag' => 'ATIVO-1']);
    Equipamento::factory()->for($area)->create(['tag' => 'INATIVO-1', 'ativo' => false]);
    Equipamento::factory()->create(['tag' => 'OUTRA-1']);

    $tecnico = User::factory()->tecnico()->create();

    $this->actingAs($tecnico)->getJson(route('areas.equipamentos', $area))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.tag', 'ATIVO-1');
});

it('cria usuário e o master redefine a senha', function () {
    $this->actingAs($this->master)->post(route('usuarios.store'), [
        'name' => 'Novo Técnico', 'matricula' => 't5000', 'email' => 'NOVO@teste.local', 'role' => 'tecnico',
        'ativo' => '1', 'password' => 'senha1234', 'password_confirmation' => 'senha1234',
    ])->assertRedirect(route('usuarios.index'));

    $user = User::where('email', 'novo@teste.local')->firstOrFail();
    expect($user->matricula)->toBe('T5000');

    $this->put(route('usuarios.senha', $user), ['nova_senha' => 'outra1234', 'nova_senha_confirmation' => 'outra1234'])
        ->assertSessionHasNoErrors();

    expect(Hash::check('outra1234', $user->fresh()->password))->toBeTrue();
});

it('impede o master de se desativar', function () {
    $this->actingAs($this->master)->patch(route('usuarios.alternar', $this->master));
    expect($this->master->fresh()->ativo)->toBeTrue();
});

it('salva equipe com membros', function () {
    $this->actingAs($this->master)->post(route('equipes.store'), [
        'nome' => 'Hidráulica', 'especialidade' => 'Hidráulica', 'ativo' => '1',
        'membros' => [
            ['nome' => 'Fulano', 'matricula' => 'H1'],
            ['nome' => '', 'matricula' => ''],
            ['nome' => 'Beltrano', 'matricula' => 'H2'],
        ],
    ])->assertRedirect(route('equipes.index'));

    $equipe = Equipe::where('nome', 'Hidráulica')->firstOrFail();
    expect($equipe->membros)->toHaveCount(2);

    $this->put(route('equipes.update', $equipe), [
        'nome' => 'Hidráulica', 'ativo' => '1', 'membros' => [['nome' => 'Só um', 'matricula' => '']],
    ]);
    expect($equipe->fresh()->membros)->toHaveCount(1);
});

it('cria categoria e motivo', function () {
    $this->actingAs($this->master)->post(route('categorias.store'), ['nome' => 'Hidráulica', 'ativo' => '1'])->assertRedirect();
    $this->post(route('motivos.store'), ['descricao' => 'Foto escura', 'ordem' => 11, 'exige_comentario' => '1', 'ativo' => '1'])->assertRedirect();

    expect(Categoria::where('nome', 'Hidráulica')->exists())->toBeTrue()
        ->and(MotivoReprovacao::where('descricao', 'Foto escura')->first()->exige_comentario)->toBeTrue();
});

it('edita os prazos de SLA', function () {
    $this->actingAs($this->master)->put(route('sla.update'), [
        'horas' => ['critica' => 12, 'alta' => 48, 'media' => 120, 'baixa' => 240],
    ])->assertSessionHasNoErrors();

    expect(SlaPrazo::horasPara(Criticidade::Critica))->toBe(12);

    $this->put(route('sla.update'), ['horas' => ['critica' => 0]])->assertSessionHasErrors(['horas.critica', 'horas.alta']);
});

it('renderiza as telas de edição de todos os cadastros e o perfil', function () {
    $this->actingAs($this->master);
    $equipe = Equipe::factory()->create();
    $equipe->membros()->create(['nome' => 'Fulano', 'matricula' => 'X1']);

    $this->get(route('usuarios.edit', User::factory()->create()))->assertOk()->assertSee('Redefinir senha');
    $this->get(route('areas.edit', Area::factory()->create()))->assertOk();
    $this->get(route('equipamentos.edit', Equipamento::factory()->create()))->assertOk();
    $this->get(route('categorias.edit', Categoria::factory()->create()))->assertOk();
    $this->get(route('motivos.edit', MotivoReprovacao::factory()->create()))->assertOk();
    $this->get(route('equipes.edit', $equipe))->assertOk()->assertSee('Fulano');
    $this->get(route('perfil.edit'))->assertOk()->assertSee('Alterar senha');
});
