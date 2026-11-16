<?php

namespace App\Http\Controllers\Cadastros;

use App\Enums\Criticidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\EquipamentoRequest;
use App\Models\Area;
use App\Models\Equipamento;
use App\Services\CadastroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipamentoController extends Controller
{
    public function __construct(private CadastroService $cadastros) {}

    public function index(Request $request): View
    {
        $equipamentos = Equipamento::query()
            ->with('area')
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['tag', 'nome'])
            ->when($request->input('area_id'), fn ($q, $a) => $q->where('area_id', $a))
            ->when($request->input('criticidade'), fn ($q, $c) => $q->where('criticidade', $c))
            ->orderBy('tag')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.equipamentos.index', [
            'equipamentos' => $equipamentos,
            'areas' => $this->areasOptions(),
            'criticidades' => Criticidade::options(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('cadastros.equipamentos.form', [
            'equipamento' => new Equipamento(['ativo' => true, 'area_id' => $request->integer('area_id') ?: null]),
            'areas' => $this->areasOptions(),
            'criticidades' => Criticidade::options(),
        ]);
    }

    public function store(EquipamentoRequest $request): RedirectResponse
    {
        Equipamento::create($request->validated());

        return redirect()->route('equipamentos.index')->with('success', 'Equipamento cadastrado com sucesso.');
    }

    public function edit(Equipamento $equipamento): View
    {
        return view('cadastros.equipamentos.form', [
            'equipamento' => $equipamento,
            'areas' => $this->areasOptions(),
            'criticidades' => Criticidade::options(),
        ]);
    }

    public function update(EquipamentoRequest $request, Equipamento $equipamento): RedirectResponse
    {
        $equipamento->update($request->validated());

        return redirect()->route('equipamentos.index')->with('success', 'Equipamento atualizado com sucesso.');
    }

    public function destroy(Equipamento $equipamento): RedirectResponse
    {
        return redirect()->route('equipamentos.index')->with('success', $this->cadastros->excluir($equipamento, 'equipamento'));
    }

    public function alternar(Equipamento $equipamento): RedirectResponse
    {
        return back()->with('success', $this->cadastros->alternarAtivo($equipamento, 'equipamento'));
    }

    private function areasOptions(): array
    {
        return Area::orderBy('codigo')->get()->mapWithKeys(fn (Area $a) => [$a->id => $a->nome_completo])->all();
    }
}
