<?php

namespace App\Http\Controllers;

use App\Enums\Criticidade;
use App\Enums\StatusOS;
use App\Enums\TipoFoto;
use App\Http\Requests\RelatorioFiltroRequest;
use App\Models\Area;
use App\Models\Equipamento;
use App\Models\Equipe;
use App\Models\OrdemServico;
use App\Services\RelatorioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function __construct(private RelatorioService $service) {}

    public function index(Request $request): View
    {
        return view('relatorios.index', $this->opcoesFiltro() + ['tipos' => RelatorioService::TIPOS]);
    }

    /** Prévia em HTML. */
    public function show(RelatorioFiltroRequest $request, string $tipo): View
    {
        $this->validarTipo($tipo);

        return view('relatorios.show', $this->opcoesFiltro() + [
            'tipo' => $tipo,
            'tipos' => RelatorioService::TIPOS,
            'relatorio' => $this->service->gerar($tipo, $request->filtros()),
            'filtrosDescritos' => $this->descreverFiltros($request->filtros()),
        ]);
    }

    public function pdf(RelatorioFiltroRequest $request, string $tipo): Response
    {
        $this->validarTipo($tipo);
        $relatorio = $this->service->gerar($tipo, $request->filtros());

        return $this->renderizarPdf('relatorios.pdf.tabela', [
            'relatorio' => $relatorio,
            'filtrosDescritos' => $this->descreverFiltros($request->filtros()),
        ], $relatorio->orientacao, "relatorio-{$tipo}-".now()->format('Ymd-His').'.pdf');
    }

    /** Ficha individual da OS, com fotos de antes e depois embutidas em base64. */
    public function fichaOs(OrdemServico $ordemServico): Response
    {
        $ordemServico->load([
            'equipe.membros', 'responsavel', 'historico.user',
            'ocorrencia.user', 'ocorrencia.area', 'ocorrencia.equipamento', 'ocorrencia.categoria',
            'ocorrencia.fotos', 'ocorrencia.analises.user', 'ocorrencia.analises.motivo',
        ]);

        $fotos = $ordemServico->ocorrencia->fotos;

        return $this->renderizarPdf('relatorios.pdf.ficha-os', [
            'os' => $ordemServico,
            'ocorrencia' => $ordemServico->ocorrencia,
            'fotosAntes' => $fotos->where('tipo', TipoFoto::Antes)->map->dataUri()->filter()->values(),
            'fotosDepois' => $fotos->where('tipo', TipoFoto::Depois)->map->dataUri()->filter()->values(),
            'relatorio' => (object) ['titulo' => 'Ficha da OS '.$ordemServico->numero],
            'filtrosDescritos' => ['Ocorrência' => $ordemServico->ocorrencia->numero],
        ], 'portrait', "ficha-{$ordemServico->numero}.pdf");
    }

    private function renderizarPdf(string $view, array $dados, string $orientacao, string $arquivo): Response
    {
        $dados['geradoPor'] = auth()->user()->name;
        $dados['geradoEm'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView($view, $dados)
            ->setPaper('a4', $orientacao)
            ->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'dpi' => 96]);

        // Rodapé com paginação em todas as páginas
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fonte = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(
            $canvas->get_width() - 110, $canvas->get_height() - 28,
            'Página {PAGE_NUM} de {PAGE_COUNT}', $fonte, 8, [0.4, 0.4, 0.4],
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$arquivo.'"',
        ]);
    }

    private function validarTipo(string $tipo): void
    {
        abort_unless(array_key_exists($tipo, RelatorioService::TIPOS), 404);
    }

    private function opcoesFiltro(): array
    {
        return [
            'areas' => Area::orderBy('codigo')->get()->mapWithKeys(fn ($a) => [$a->id => $a->nome_completo])->all(),
            'equipes' => Equipe::orderBy('nome')->pluck('nome', 'id')->all(),
            'statuses' => StatusOS::options(),
            'criticidades' => Criticidade::options(),
        ];
    }

    private function descreverFiltros(array $f): array
    {
        return array_filter([
            'Período' => $f['inicio']->format('d/m/Y').' a '.$f['fim']->format('d/m/Y'),
            'Área' => $f['area_id'] ? Area::withTrashed()->find($f['area_id'])?->nome_completo : null,
            'Equipamento' => $f['equipamento_id'] ? Equipamento::withTrashed()->find($f['equipamento_id'])?->nome_completo : null,
            'Status da OS' => $f['status'] ? StatusOS::from($f['status'])->label() : null,
            'Criticidade' => $f['criticidade'] ? Criticidade::from($f['criticidade'])->label() : null,
            'Equipe' => $f['equipe_id'] ? Equipe::withTrashed()->find($f['equipe_id'])?->nome : null,
        ]);
    }
}
