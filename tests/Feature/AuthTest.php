<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

it('exibe a tela de login', function () {
    $this->get('/login')->assertOk()->assertSee('Matrícula ou E-mail')->assertSee('Inspeções realizadas');
});

it('redireciona visitante para o login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('faz login por e-mail', function () {
    $user = User::factory()->create(['email' => 'joao@teste.local']);

    $this->post('/login', ['login' => 'joao@teste.local', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('faz login por matrícula', function () {
    $user = User::factory()->create(['matricula' => 'T9999']);

    $this->post('/login', ['login' => 'T9999', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('recusa senha incorreta', function () {
    User::factory()->create(['email' => 'joao@teste.local']);

    $this->post('/login', ['login' => 'joao@teste.local', 'password' => 'errada'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('recusa usuário inativo', function () {
    User::factory()->inativo()->create(['email' => 'inativo@teste.local']);

    $this->post('/login', ['login' => 'inativo@teste.local', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('desloga usuário desativado durante a sessão', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertOk();

    $user->update(['ativo' => false]);

    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

it('limita tentativas de login (throttle 5/min)', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['login' => 'x@x.com', 'password' => 'errada']);
    }

    $this->post('/login', ['login' => 'x@x.com', 'password' => 'errada'])->assertStatus(429);
});

it('faz logout', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('altera a própria senha', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/perfil/senha', [
        'senha_atual' => 'password',
        'password' => 'novaSenha123',
        'password_confirmation' => 'novaSenha123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('novaSenha123', $user->fresh()->password))->toBeTrue();
});

it('exige a senha atual correta para alterar', function () {
    $this->actingAs(User::factory()->create())->put('/perfil/senha', [
        'senha_atual' => 'errada',
        'password' => 'novaSenha123',
        'password_confirmation' => 'novaSenha123',
    ])->assertSessionHasErrors('senha_atual');
});

describe('middleware role', function () {
    beforeEach(function () {
        Route::middleware(['web', 'auth', 'role:gerente'])->get('/_teste/gerente', fn () => 'ok');
        Route::middleware(['web', 'auth', 'role:supervisor,gerente'])->get('/_teste/gestao', fn () => 'ok');
    });

    it('permite o perfil correto', function () {
        $this->actingAs(User::factory()->gerente()->create())->get('/_teste/gerente')->assertOk();
    });

    it('bloqueia outro perfil com 403', function () {
        $this->actingAs(User::factory()->tecnico()->create())->get('/_teste/gerente')->assertForbidden();
        $this->actingAs(User::factory()->supervisor()->create())->get('/_teste/gerente')->assertForbidden();
    });

    it('aceita múltiplos perfis', function () {
        $this->actingAs(User::factory()->supervisor()->create())->get('/_teste/gestao')->assertOk();
    });

    it('libera o master em qualquer grupo', function () {
        $this->actingAs(User::factory()->master()->create())->get('/_teste/gerente')->assertOk();
    });
});

it('mostra menu conforme o perfil', function (Role $role) {
    $this->actingAs(User::factory()->role($role)->create())
        ->get('/')
        ->assertOk()
        ->assertSee($role->label());
})->with(Role::cases());
