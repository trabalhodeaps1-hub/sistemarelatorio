<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\EquipeRequest;
use App\Models\Equipe;
use App\Services\CadastroService;
use App\Services\EquipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipeController extends Controller
{
    public function __construct(private CadastroService $cadastros, private EquipeService $equipes) {}

    public function index(Request $request): View
    {
        $equipes = Equipe::query()
            ->withCount('membros')
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['nome', 'especialidade'])
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.equipes.index', compact('equipes'));
    }

    public function create(): View
    {
        return view('cadastros.equipes.form', ['equipe' => new Equipe(['ativo' => true])]);
    }

    public function store(EquipeRequest $request): RedirectResponse
    {
        $this->equipes->salvar(new Equipe, $request->validated());

        return redirect()->route('equipes.index')->with('success', 'Equipe cadastrada com sucesso.');
    }

    public function edit(Equipe $equipe): View
    {
        return view('cadastros.equipes.form', ['equipe' => $equipe->load('membros')]);
    }

    public function update(EquipeRequest $request, Equipe $equipe): RedirectResponse
    {
        $this->equipes->salvar($equipe, $request->validated());

        return redirect()->route('equipes.index')->with('success', 'Equipe atualizada com sucesso.');
    }

    public function destroy(Equipe $equipe): RedirectResponse
    {
        return redirect()->route('equipes.index')->with('success', $this->cadastros->excluir($equipe, 'equipe'));
    }

    public function alternar(Equipe $equipe): RedirectResponse
    {
        return back()->with('success', $this->cadastros->alternarAtivo($equipe, 'equipe'));
    }
}
