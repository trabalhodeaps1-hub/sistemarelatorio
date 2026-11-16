<?php

namespace App\Http\Controllers;

use App\Enums\Criticidade;
use App\Enums\StatusOcorrencia;
use App\Http\Requests\OcorrenciaRequest;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Ocorrencia;
use App\Services\OcorrenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OcorrenciaController extends Controller
{
    public function __construct(private OcorrenciaService $service) {}

    /** "Minhas ocorrências" */
    public function index(Request $request): View
    {
        $ocorrencias = $request->user()->ocorrencias()
            ->with(['area', 'equipamento', 'categoria', 'ordemServico'])
            ->withCount('fotos')
            ->status($request->input('status'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $contagem = $request->user()->ocorrencias()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('ocorrencias.index', [
            'ocorrencias' => $ocorrencias,
            'contagem' => $contagem,
            'statuses' => StatusOcorrencia::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ocorrencia::class);

        return view('ocorrencias.form', $this->dadosFormulario(new Ocorrencia));
    }

    public function store(OcorrenciaRequest $request): RedirectResponse
    {
        $ocorrencia = $this->service->registrar($request->dados(), $request->file('fotos', []), $request->user());

        return redirect()->route('ocorrencias.show', $ocorrencia)
            ->with('success', "Ocorrência {$ocorrencia->numero} registrada e enviada para análise.");
    }

    public function show(Ocorrencia $ocorrencia): View
    {
        $this->authorize('view', $ocorrencia);

        $ocorrencia->load([
            'user', 'area', 'equipamento', 'categoria', 'fotos',
            'analises.user', 'analises.motivo', 'ordemServico.equipe', 'ordemServico.historico.user',
        ]);

        return view('ocorrencias.show', compact('ocorrencia'));
    }

    public function edit(Ocorrencia $ocorrencia): View
    {
        $this->authorize('update', $ocorrencia);

        $ocorrencia->load(['fotosAntes', 'ultimaAnalise.motivo', 'ultimaAnalise.user']);

        return view('ocorrencias.form', $this->dadosFormulario($ocorrencia));
    }

    public function update(OcorrenciaRequest $request, Ocorrencia $ocorrencia): RedirectResponse
    {
        $ocorrencia = $this->service->reenviar(
            $ocorrencia,
            $request->dados(),
            $request->file('fotos', []),
            array_map('intval', $request->input('remover_fotos', [])),
            $request->user(),
        );

        return redirect()->route('ocorrencias.show', $ocorrencia)
            ->with('success', "Ocorrência {$ocorrencia->numero} corrigida e reenviada para análise.");
    }

    public function cancelar(Ocorrencia $ocorrencia): RedirectResponse
    {
        $this->authorize('cancelar', $ocorrencia);

        $this->service->cancelar($ocorrencia);

        return redirect()->route('ocorrencias.show', $ocorrencia)->with('success', 'Ocorrência cancelada.');
    }

    private function dadosFormulario(Ocorrencia $ocorrencia): array
    {
        $areas = Area::ativos()->orderBy('codigo')->get();

        return [
            'ocorrencia' => $ocorrencia,
            'areas' => $areas->mapWithKeys(fn (Area $a) => [$a->id => $a->nome_completo])->all(),
            'categorias' => Categoria::ativos()->orderBy('nome')->pluck('nome', 'id')->all(),
            'criticidades' => Criticidade::options(),
            'maxFotos' => (int) config('sistema.fotos.max_por_envio', 5),
        ];
    }
}
