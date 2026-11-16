<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\MotivoReprovacaoRequest;
use App\Models\MotivoReprovacao;
use App\Services\CadastroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MotivoReprovacaoController extends Controller
{
    public function __construct(private CadastroService $cadastros) {}

    public function index(Request $request): View
    {
        $motivos = MotivoReprovacao::query()
            ->filtroLista($request->input('busca'), $request->input('situacao'), ['descricao'])
            ->orderBy('ordem')
            ->orderBy('descricao')
            ->paginate(20)
            ->withQueryString();

        return view('cadastros.motivos.index', compact('motivos'));
    }

    public function create(): View
    {
        $ordem = (int) MotivoReprovacao::max('ordem') + 1;

        return view('cadastros.motivos.form', ['motivo' => new MotivoReprovacao(['ativo' => true, 'ordem' => $ordem])]);
    }

    public function store(MotivoReprovacaoRequest $request): RedirectResponse
    {
        MotivoReprovacao::create($request->validated());

        return redirect()->route('motivos.index')->with('success', 'Motivo cadastrado com sucesso.');
    }

    public function edit(MotivoReprovacao $motivo): View
    {
        return view('cadastros.motivos.form', compact('motivo'));
    }

    public function update(MotivoReprovacaoRequest $request, MotivoReprovacao $motivo): RedirectResponse
    {
        $motivo->update($request->validated());

        return redirect()->route('motivos.index')->with('success', 'Motivo atualizado com sucesso.');
    }

    public function destroy(MotivoReprovacao $motivo): RedirectResponse
    {
        return redirect()->route('motivos.index')->with('success', $this->cadastros->excluir($motivo, 'motivo'));
    }

    public function alternar(MotivoReprovacao $motivo): RedirectResponse
    {
        return back()->with('success', $this->cadastros->alternarAtivo($motivo, 'motivo'));
    }
}
