<?php

namespace App\Http\Controllers\Cadastros;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\ResetarSenhaRequest;
use App\Http\Requests\Cadastros\UsuarioRequest;
use App\Models\User;
use App\Services\CadastroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private CadastroService $cadastros) {}

    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['name', 'email', 'matricula'])
            ->when($request->input('role'), fn ($q, $r) => $q->where('role', $r))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.usuarios.index', ['usuarios' => $usuarios, 'roles' => Role::options()]);
    }

    public function create(): View
    {
        return view('cadastros.usuarios.form', ['usuario' => new User(['ativo' => true]), 'roles' => Role::options()]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create($request->dados());

        return redirect()->route('usuarios.index')->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $usuario): View
    {
        return view('cadastros.usuarios.form', ['usuario' => $usuario, 'roles' => Role::options()]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $dados = $request->dados();

        if ($usuario->is($request->user()) && (! $dados['ativo'] || $dados['role'] !== Role::Master->value)) {
            throw ValidationException::withMessages(['role' => 'Você não pode desativar nem rebaixar o próprio usuário.']);
        }

        $usuario->update($dados);

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'Você não pode excluir o próprio usuário.');
        }

        return redirect()->route('usuarios.index')->with('success', $this->cadastros->excluir($usuario, 'usuário'));
    }

    public function alternar(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'Você não pode desativar o próprio usuário.');
        }

        return back()->with('success', $this->cadastros->alternarAtivo($usuario, 'usuário'));
    }

    public function resetarSenha(ResetarSenhaRequest $request, User $usuario): RedirectResponse
    {
        $usuario->update(['password' => $request->validated('nova_senha')]);

        return back()->with('success', "Senha de {$usuario->name} redefinida.");
    }
}
