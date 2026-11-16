<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\CategoriaRequest;
use App\Models\Categoria;
use App\Services\CadastroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function __construct(private CadastroService $cadastros) {}

    public function index(Request $request): View
    {
        $categorias = Categoria::query()
            ->withCount('ocorrencias')
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['nome'])
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        return view('cadastros.categorias.form', ['categoria' => new Categoria(['ativo' => true])]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        return redirect()->route('categorias.index')->with('success', 'Categoria cadastrada com sucesso.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('cadastros.categorias.form', compact('categoria'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()->route('categorias.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        return redirect()->route('categorias.index')->with('success', $this->cadastros->excluir($categoria, 'categoria'));
    }

    public function alternar(Categoria $categoria): RedirectResponse
    {
        return back()->with('success', $this->cadastros->alternarAtivo($categoria, 'categoria'));
    }
}
