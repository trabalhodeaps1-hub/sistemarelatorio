<?php

namespace App\Http\Controllers;

use App\Enums\Criticidade;
use App\Http\Requests\AprovarOcorrenciaRequest;
use App\Http\Requests\ReprovarOcorrenciaRequest;
use App\Models\Area;
use App\Models\MotivoReprovacao;
use App\Models\Ocorrencia;
use App\Services\AnaliseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnaliseController extends Controller
{
    public function __construct(private AnaliseService $service) {}

    /** Fila de pendentes: criticidade decrescente, data crescente. */
    public function index(Request $request): View
    {
        $ocorrencias = Ocorrencia::pendentes()
            ->with(['user', 'area', 'equipamento', 'categoria'])
            ->withCount('fotos')
            ->when($request->input('area_id'), fn ($q, $a) => $q->where('area_id', $a))
            ->when($request->input('criticidade'), fn ($q, $c) => $q->where('criticidade', $c))
            ->ordemDeFila()
            ->paginate(20)
            ->withQueryString();

        return view('analises.index', [
            'ocorrencias' => $ocorrencias,
            'areas' => Area::ativos()->orderBy('codigo')->pluck('nome', 'id')->all(),
            'criticidades' => Criticidade::options(),
            'totalCriticas' => Ocorrencia::pendentes()->where('criticidade', Criticidade::Critica->value)->count(),
        ]);
    }

    public function show(Ocorrencia $ocorrencia): View|RedirectResponse
    {
        if (! request()->user()->can('analisar', $ocorrencia)) {
            $this->authorize('view', $ocorrencia);

            return redirect()->route('ocorrencias.show', $ocorrencia)
                ->with('info', 'Esta ocorrência não está pendente de análise.');
        }

        $ocorrencia->load(['user', 'area', 'equipamento', 'categoria', 'fotos', 'analises.user', 'analises.motivo']);

        return view('analises.show', [
            'ocorrencia' => $ocorrencia,
            'criticidades' => Criticidade::options(),
            'motivos' => MotivoReprovacao::ativos()->orderBy('ordem')->get(),
        ]);
    }

    public function aprovar(AprovarOcorrenciaRequest $request, Ocorrencia $ocorrencia): RedirectResponse
    {
        $os = $this->service->aprovar(
            $ocorrencia,
            $request->user(),
            Criticidade::from($request->validated('criticidade')),
            $request->validated('comentario'),
        );

        return redirect()->route('analises.index')
            ->with('success', "Ocorrência {$ocorrencia->numero} aprovada. {$os->numero} aberta com prazo até {$os->prazo_previsto->format('d/m/Y H:i')}.");
    }

    public function reprovar(ReprovarOcorrenciaRequest $request, Ocorrencia $ocorrencia): RedirectResponse
    {
        $this->service->reprovar($ocorrencia, $request->user(), $request->motivo(), $request->validated('comentario'));

        return redirect()->route('analises.index')
            ->with('success', "Ocorrência {$ocorrencia->numero} reprovada e devolvida ao técnico.");
    }
}
