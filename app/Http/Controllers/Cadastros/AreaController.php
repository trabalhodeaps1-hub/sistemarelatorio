<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\AreaRequest;
use App\Models\Area;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function __construct(private CadastroService $cadastros) {}

    public function index(Request $request): View
    {
        $areas = Area::query()
            ->withCount('equipamentos')
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['codigo', 'nome'])
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.areas.index', compact('areas'));
    }

    public function create(): View
    {
        return view('cadastros.areas.form', ['area' => new Area(['ativo' => true])]);
    }

    public function store(AreaRequest $request): RedirectResponse
    {
        Area::create($request->validated());

        return redirect()->route('areas.index')->with('success', 'Área cadastrada com sucesso.');
    }

    public function edit(Area $area): View
    {
        return view('cadastros.areas.form', compact('area'));
    }

    public function update(AreaRequest $request, Area $area): RedirectResponse
    {
        $area->update($request->validated());

        return redirect()->route('areas.index')->with('success', 'Área atualizada com sucesso.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        return redirect()->route('areas.index')->with('success', $this->cadastros->excluir($area, 'área'));
    }

    public function alternar(Area $area): RedirectResponse
    {
        return back()->with('success', $this->cadastros->alternarAtivo($area, 'área'));
    }

    /** JSON para o select dependente de equipamentos. */
    public function equipamentos(Area $area): JsonResponse
    {
        $equipamentos = $area->equipamentos()
            ->ativos()
            ->orderBy('tag')
            ->get(['id', 'tag', 'nome', 'criticidade'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'tag' => $e->tag,
                'nome' => $e->nome,
                'criticidade' => $e->criticidade->value,
                'rotulo' => "{$e->tag} — {$e->nome}",
            ]);

        return response()->json($equipamentos);
    }
}
