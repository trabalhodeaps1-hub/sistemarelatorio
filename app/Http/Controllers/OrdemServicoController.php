<?php

namespace App\Http\Controllers;

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use App\Http\Requests\TransicaoOSRequest;
use App\Models\Area;
use App\Models\Equipe;
use App\Models\OrdemServico;
use App\Services\OrdemServicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdemServicoController extends Controller
{
    public function __construct(private OrdemServicoService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', OrdemServico::class);

        $situacao = $request->input('situacao');

        $ordens = OrdemServico::query()
            ->with(['ocorrencia.area', 'ocorrencia.equipamento', 'equipe'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('criticidade'), fn ($q, $c) => $q->where('criticidade', $c))
            ->when($request->input('equipe_id'), fn ($q, $e) => $q->where('equipe_id', $e))
            ->when($request->input('area_id'), fn ($q, $a) => $q->whereHas('ocorrencia', fn ($o) => $o->where('area_id', $a)))
            ->when($request->input('busca'), fn ($q, $b) => $q->where(fn ($w) => $w
                ->where('numero', 'ilike', "%{$b}%")
                ->orWhereHas('ocorrencia', fn ($o) => $o->where('titulo', 'ilike', "%{$b}%")->orWhere('numero', 'ilike', "%{$b}%"))))
            ->when($situacao === 'atrasadas', fn ($q) => $q->atrasadas())
            ->when($situacao === 'vencendo', fn ($q) => $q->vencendoEm24h())
            ->when($situacao === 'andamento', fn ($q) => $q->emAndamento())
            ->orderByRaw("CASE WHEN status IN ('concluida','cancelada') THEN 1 ELSE 0 END")
            ->orderBy('prazo_previsto')
            ->paginate(20)
            ->withQueryString();

        return view('ordens-servico.index', [
            'ordens' => $ordens,
            'statuses' => StatusOS::options(),
            'criticidades' => Criticidade::options(),
            'areas' => Area::orderBy('codigo')->pluck('nome', 'id')->all(),
            'equipes' => Equipe::orderBy('nome')->pluck('nome', 'id')->all(),
            'contadores' => [
                'atrasadas' => OrdemServico::atrasadas()->count(),
                'vencendo' => OrdemServico::vencendoEm24h()->count(),
                'andamento' => OrdemServico::emAndamento()->count(),
            ],
        ]);
    }

    public function show(OrdemServico $ordemServico): View
    {
        $this->authorize('view', $ordemServico);

        $ordemServico->load([
            'equipe.membros', 'responsavel', 'historico.user',
            'ocorrencia.user', 'ocorrencia.area', 'ocorrencia.equipamento', 'ocorrencia.categoria',
            'ocorrencia.fotos', 'ocorrencia.analises.user', 'ocorrencia.analises.motivo',
        ]);

        return view('ordens-servico.show', [
            'os' => $ordemServico,
            'ocorrencia' => $ordemServico->ocorrencia,
            'transicoes' => $ordemServico->status->transicoesPermitidas(),
            'equipes' => Equipe::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function transicionar(TransicaoOSRequest $request, OrdemServico $ordemServico): RedirectResponse
    {
        $os = $this->service->transicionar(
            $ordemServico,
            $request->novoStatus(),
            $request->user(),
            $request->safe()->only(['equipe_id', 'prazo_previsto', 'comentario', 'observacao_conclusao']),
            $request->file('fotos', []),
        );

        return redirect()->route('ordens-servico.show', $os)
            ->with('success', "{$os->numero}: status alterado para {$os->status->label()}.");
    }
}
