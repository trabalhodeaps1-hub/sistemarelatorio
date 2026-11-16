<?php

namespace App\Services;

use App\Enums\Criticidade;
use App\Enums\StatusOcorrencia;
use App\Enums\StatusOS;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Relatórios gerenciais. Cada relatório é resolvido com uma única query
 * e devolvido como um Relatorio genérico (prévia HTML e PDF usam o mesmo formato).
 */
class RelatorioService
{
    public const TIPOS = [
        'os-aprovadas' => ['OS aprovadas', 'Ordens de serviço geradas no período, com aprovador e data da aprovação.', 'check-circle'],
        'reprovadas' => ['Ocorrências reprovadas', 'Reprovações no período, com motivo e supervisor responsável.', 'x-octagon'],
        'os-concluidas' => ['OS concluídas', 'Prazo previsto x realizado e equipe executora.', 'check2-all'],
        'ranking-equipamentos' => ['Ranking de equipamentos', 'Equipamentos com mais ocorrências no período.', 'cpu'],
        'ranking-areas' => ['Ranking de áreas', 'Áreas com mais ocorrências no período.', 'diagram-3'],
        'indicadores' => ['Indicadores de prazo', 'Tempo médio de aprovação e de execução e OS em atraso, por criticidade.', 'speedometer2'],
    ];

    public function gerar(string $tipo, array $f): Relatorio
    {
        return match ($tipo) {
            'os-aprovadas' => $this->osAprovadas($f),
            'reprovadas' => $this->reprovadas($f),
            'os-concluidas' => $this->osConcluidas($f),
            'ranking-equipamentos' => $this->rankingEquipamentos($f),
            'ranking-areas' => $this->rankingAreas($f),
            'indicadores' => $this->indicadores($f),
        };
    }

    // ---------------------------------------------------------------- 1
    private function osAprovadas(array $f): Relatorio
    {
        $linhas = $this->baseOs($f)
            ->join('ocorrencia_analises as an', fn ($j) => $j->on('an.ocorrencia_id', '=', 'oc.id')->where('an.acao', StatusOcorrencia::Aprovada->value))
            ->join('users as ap', 'ap.id', '=', 'an.user_id')
            ->whereBetween('an.created_at', [$f['inicio'], $f['fim']])
            ->orderBy('an.created_at')
            ->get([
                'os.numero', 'oc.numero as oc_numero', 'oc.titulo', 'a.codigo as area', 'e.tag',
                'os.criticidade', 'ap.name as aprovador', 'an.created_at as aprovado_em', 'os.status', 'eq.nome as equipe',
            ])
            ->map(fn ($r) => [
                'numero' => $r->numero,
                'ocorrencia' => $r->oc_numero,
                'titulo' => $r->titulo,
                'local' => $r->area.' · '.($r->tag ?? 'Estrutura'),
                'criticidade' => Criticidade::from($r->criticidade)->label(),
                'aprovador' => $r->aprovador,
                'aprovado_em' => $this->data($r->aprovado_em),
                'status' => StatusOS::from($r->status)->label(),
                'equipe' => $r->equipe ?? '—',
            ])->all();

        return new Relatorio(
            'OS aprovadas', self::TIPOS['os-aprovadas'][1],
            ['numero' => 'OS', 'ocorrencia' => 'Ocorrência', 'titulo' => 'Problema', 'local' => 'Área · Equip.',
                'criticidade' => 'Criticidade', 'aprovador' => 'Aprovador', 'aprovado_em' => 'Aprovada em', 'status' => 'Status', 'equipe' => 'Equipe'],
            $linhas,
            ['OS aprovadas' => count($linhas)] + $this->contarPor($linhas, 'criticidade'),
            orientacao: 'landscape',
        );
    }

    // ---------------------------------------------------------------- 2
    private function reprovadas(array $f): Relatorio
    {
        $linhas = DB::table('ocorrencia_analises as an')
            ->join('ocorrencias as oc', 'oc.id', '=', 'an.ocorrencia_id')
            ->join('areas as a', 'a.id', '=', 'oc.area_id')
            ->leftJoin('equipamentos as e', 'e.id', '=', 'oc.equipamento_id')
            ->join('users as tec', 'tec.id', '=', 'oc.user_id')
            ->join('users as sup', 'sup.id', '=', 'an.user_id')
            ->leftJoin('motivos_reprovacao as m', 'm.id', '=', 'an.motivo_reprovacao_id')
            ->where('an.acao', StatusOcorrencia::Reprovada->value)
            ->whereNull('oc.deleted_at')
            ->whereBetween('an.created_at', [$f['inicio'], $f['fim']])
            ->tap(fn ($q) => $this->filtrarOcorrencia($q, $f))
            ->orderBy('an.created_at')
            ->get([
                'oc.numero', 'oc.titulo', 'a.codigo as area', 'e.tag', 'tec.name as tecnico',
                'm.descricao as motivo', 'an.comentario', 'sup.name as supervisor', 'an.created_at',
            ])
            ->map(fn ($r) => [
                'numero' => $r->numero,
                'titulo' => $r->titulo,
                'local' => $r->area.' · '.($r->tag ?? 'Estrutura'),
                'tecnico' => $r->tecnico,
                'motivo' => $r->motivo ?? '—',
                'comentario' => $r->comentario ?? '',
                'supervisor' => $r->supervisor,
                'data' => $this->data($r->created_at),
            ])->all();

        return new Relatorio(
            'Ocorrências reprovadas', self::TIPOS['reprovadas'][1],
            ['numero' => 'Ocorrência', 'titulo' => 'Título', 'local' => 'Área · Equip.', 'tecnico' => 'Técnico',
                'motivo' => 'Motivo', 'comentario' => 'Comentário', 'supervisor' => 'Reprovada por', 'data' => 'Data'],
            $linhas,
            ['Reprovações' => count($linhas)] + array_slice($this->contarPor($linhas, 'motivo'), 0, 5, true),
            orientacao: 'landscape',
        );
    }

    // ---------------------------------------------------------------- 3
    private function osConcluidas(array $f): Relatorio
    {
        $linhas = $this->baseOs(array_merge($f, ['status' => null]))
            ->where('os.status', StatusOS::Concluida->value)
            ->whereBetween('os.concluida_em', [$f['inicio'], $f['fim']])
            ->orderBy('os.concluida_em')
            ->get([
                'os.numero', 'oc.titulo', 'a.codigo as area', 'e.tag', 'eq.nome as equipe', 'os.criticidade',
                'os.prazo_previsto', 'os.concluida_em',
                DB::raw('EXTRACT(EPOCH FROM (os.concluida_em - os.prazo_previsto)) / 3600 as desvio_h'),
            ])
            ->map(fn ($r) => [
                'numero' => $r->numero,
                'titulo' => $r->titulo,
                'local' => $r->area.' · '.($r->tag ?? 'Estrutura'),
                'equipe' => $r->equipe ?? '—',
                'criticidade' => Criticidade::from($r->criticidade)->label(),
                'prazo' => $this->data($r->prazo_previsto),
                'concluida' => $this->data($r->concluida_em),
                'desvio' => $r->desvio_h === null ? '—' : $this->horas((float) $r->desvio_h, true),
                'situacao' => $r->desvio_h !== null && $r->desvio_h > 0 ? 'Atrasada' : 'No prazo',
            ])->all();

        $total = count($linhas);
        $noPrazo = collect($linhas)->where('situacao', 'No prazo')->count();

        return new Relatorio(
            'OS concluídas', self::TIPOS['os-concluidas'][1],
            ['numero' => 'OS', 'titulo' => 'Problema', 'local' => 'Área · Equip.', 'equipe' => 'Equipe', 'criticidade' => 'Criticidade',
                'prazo' => 'Prazo previsto', 'concluida' => 'Concluída em', 'desvio' => 'Desvio', 'situacao' => 'Situação'],
            $linhas,
            [
                'OS concluídas' => $total,
                'No prazo' => $noPrazo,
                'Com atraso' => $total - $noPrazo,
                '% no prazo' => $total ? number_format($noPrazo / $total * 100, 1, ',', '.').'%' : '—',
            ],
            numericas: ['desvio'],
            orientacao: 'landscape',
        );
    }

    // ---------------------------------------------------------------- 4
    private function rankingEquipamentos(array $f): Relatorio
    {
        $linhas = $this->baseRanking($f)
            ->join('equipamentos as e', 'e.id', '=', 'oc.equipamento_id')
            ->groupBy('e.id', 'e.tag', 'e.nome', 'a.codigo')
            ->get(array_merge(['e.tag', 'e.nome', 'a.codigo as area'], $this->colunasRanking()))
            ->values()
            ->map(fn ($r, $i) => [
                'posicao' => $i + 1 .'º',
                'equipamento' => "{$r->tag} — {$r->nome}",
                'area' => $r->area,
                ...$this->valoresRanking($r),
            ])->all();

        return new Relatorio(
            'Ranking de equipamentos', self::TIPOS['ranking-equipamentos'][1],
            ['posicao' => '#', 'equipamento' => 'Equipamento', 'area' => 'Área', ...$this->rotulosRanking()],
            $linhas,
            ['Equipamentos com ocorrência' => count($linhas), 'Ocorrências' => collect($linhas)->sum('total')],
            numericas: ['total', 'criticas', 'reprovadas', 'concluidas'],
        );
    }

    // ---------------------------------------------------------------- 5
    private function rankingAreas(array $f): Relatorio
    {
        $linhas = $this->baseRanking($f)
            ->groupBy('a.id', 'a.codigo', 'a.nome')
            ->get(array_merge(['a.codigo', 'a.nome'], $this->colunasRanking()))
            ->values()
            ->map(fn ($r, $i) => [
                'posicao' => $i + 1 .'º',
                'area' => "{$r->codigo} — {$r->nome}",
                ...$this->valoresRanking($r),
            ])->all();

        return new Relatorio(
            'Ranking de áreas', self::TIPOS['ranking-areas'][1],
            ['posicao' => '#', 'area' => 'Área', ...$this->rotulosRanking()],
            $linhas,
            ['Áreas com ocorrência' => count($linhas), 'Ocorrências' => collect($linhas)->sum('total')],
            numericas: ['total', 'criticas', 'reprovadas', 'concluidas'],
        );
    }

    // ---------------------------------------------------------------- 6
    private function indicadores(array $f): Relatorio
    {
        $andamento = "'".implode("','", array_map(fn ($s) => $s->value, StatusOS::emAndamento()))."'";

        // ROLLUP: uma linha por criticidade + a linha de total (criticidade NULL)
        $linhas = DB::table('ocorrencias as oc')
            ->leftJoin('ocorrencia_analises as an', fn ($j) => $j->on('an.ocorrencia_id', '=', 'oc.id')->where('an.acao', StatusOcorrencia::Aprovada->value))
            ->leftJoin('ordens_servico as os', 'os.ocorrencia_id', '=', 'oc.id')
            ->whereNull('oc.deleted_at')
            ->whereBetween('oc.created_at', [$f['inicio'], $f['fim']])
            ->tap(fn ($q) => $this->filtrarOcorrencia($q, $f))
            ->when($f['status'], fn ($q, $s) => $q->where('os.status', $s))
            ->when($f['equipe_id'], fn ($q, $e) => $q->where('os.equipe_id', $e))
            ->groupByRaw('ROLLUP(oc.criticidade)')
            ->orderByRaw("GROUPING(oc.criticidade), CASE oc.criticidade WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->get([
                'oc.criticidade',
                DB::raw('COUNT(*) as ocorrencias'),
                DB::raw('COUNT(an.id) as aprovadas'),
                DB::raw('AVG(EXTRACT(EPOCH FROM (an.created_at - oc.created_at))) / 3600 as tempo_aprovacao'),
                DB::raw("COUNT(*) FILTER (WHERE os.status = 'concluida') as concluidas"),
                DB::raw("AVG(EXTRACT(EPOCH FROM (os.concluida_em - COALESCE(os.iniciada_em, os.created_at)))) FILTER (WHERE os.status = 'concluida') / 3600 as tempo_execucao"),
                DB::raw("AVG(EXTRACT(EPOCH FROM (os.concluida_em - os.created_at))) FILTER (WHERE os.status = 'concluida') / 3600 as tempo_atendimento"),
                DB::raw("COUNT(*) FILTER (WHERE os.status = 'concluida' AND os.concluida_em <= os.prazo_previsto) as no_prazo"),
                DB::raw("COUNT(*) FILTER (WHERE os.status IN ({$andamento}) AND os.prazo_previsto < NOW()) as em_atraso"),
            ])
            ->map(fn ($r) => [
                'criticidade' => $r->criticidade ? Criticidade::from($r->criticidade)->label() : 'Total',
                'ocorrencias' => (int) $r->ocorrencias,
                'aprovadas' => (int) $r->aprovadas,
                'tempo_aprovacao' => $this->horas($r->tempo_aprovacao),
                'concluidas' => (int) $r->concluidas,
                'tempo_execucao' => $this->horas($r->tempo_execucao),
                'tempo_atendimento' => $this->horas($r->tempo_atendimento),
                'no_prazo' => $r->concluidas ? number_format($r->no_prazo / $r->concluidas * 100, 1, ',', '.').'%' : '—',
                'em_atraso' => (int) $r->em_atraso,
            ])->all();

        $total = collect($linhas)->firstWhere('criticidade', 'Total');

        return new Relatorio(
            'Indicadores de prazo', self::TIPOS['indicadores'][1],
            ['criticidade' => 'Criticidade', 'ocorrencias' => 'Ocorrências', 'aprovadas' => 'Aprovadas',
                'tempo_aprovacao' => 'T. médio aprovação', 'concluidas' => 'OS concluídas', 'tempo_execucao' => 'T. médio execução',
                'tempo_atendimento' => 'T. médio aprovação→conclusão', 'no_prazo' => '% no prazo', 'em_atraso' => 'OS em atraso'],
            $linhas,
            $total ? [
                'Tempo médio de aprovação' => $total['tempo_aprovacao'],
                'Tempo médio de execução' => $total['tempo_execucao'],
                'Concluídas no prazo' => $total['no_prazo'],
                'OS em atraso agora' => $total['em_atraso'],
            ] : [],
            numericas: ['ocorrencias', 'aprovadas', 'tempo_aprovacao', 'concluidas', 'tempo_execucao', 'tempo_atendimento', 'no_prazo', 'em_atraso'],
            orientacao: 'landscape',
        );
    }

    // ================================================================ apoio

    private function baseOs(array $f): Builder
    {
        return DB::table('ordens_servico as os')
            ->join('ocorrencias as oc', 'oc.id', '=', 'os.ocorrencia_id')
            ->join('areas as a', 'a.id', '=', 'oc.area_id')
            ->leftJoin('equipamentos as e', 'e.id', '=', 'oc.equipamento_id')
            ->leftJoin('equipes as eq', 'eq.id', '=', 'os.equipe_id')
            ->whereNull('oc.deleted_at')
            ->tap(fn ($q) => $this->filtrarOcorrencia($q, $f))
            ->when($f['status'], fn ($q, $s) => $q->where('os.status', $s))
            ->when($f['equipe_id'], fn ($q, $e) => $q->where('os.equipe_id', $e));
    }

    private function baseRanking(array $f): Builder
    {
        return DB::table('ocorrencias as oc')
            ->join('areas as a', 'a.id', '=', 'oc.area_id')
            ->leftJoin('ordens_servico as os', 'os.ocorrencia_id', '=', 'oc.id')
            ->whereNull('oc.deleted_at')
            ->where('oc.status', '<>', StatusOcorrencia::Cancelada->value)
            ->whereBetween('oc.created_at', [$f['inicio'], $f['fim']])
            ->tap(fn ($q) => $this->filtrarOcorrencia($q, $f))
            ->when($f['status'], fn ($q, $s) => $q->where('os.status', $s))
            ->when($f['equipe_id'], fn ($q, $e) => $q->where('os.equipe_id', $e))
            ->orderByDesc('total')
            ->orderByDesc('criticas');
    }

    private function colunasRanking(): array
    {
        return [
            DB::raw('COUNT(*) as total'),
            DB::raw("COUNT(*) FILTER (WHERE oc.criticidade = 'critica') as criticas"),
            DB::raw("COUNT(*) FILTER (WHERE oc.status = 'reprovada') as reprovadas"),
            DB::raw("COUNT(*) FILTER (WHERE os.status = 'concluida') as concluidas"),
        ];
    }

    private function valoresRanking(object $r): array
    {
        return [
            'total' => (int) $r->total,
            'criticas' => (int) $r->criticas,
            'reprovadas' => (int) $r->reprovadas,
            'concluidas' => (int) $r->concluidas,
        ];
    }

    private function rotulosRanking(): array
    {
        return ['total' => 'Ocorrências', 'criticas' => 'Críticas', 'reprovadas' => 'Reprovadas', 'concluidas' => 'OS concluídas'];
    }

    private function filtrarOcorrencia(Builder $q, array $f): void
    {
        $q->when($f['area_id'], fn ($q, $a) => $q->where('oc.area_id', $a))
            ->when($f['equipamento_id'], fn ($q, $e) => $q->where('oc.equipamento_id', $e))
            ->when($f['criticidade'], fn ($q, $c) => $q->where('oc.criticidade', $c));
    }

    private function contarPor(array $linhas, string $campo): array
    {
        return collect($linhas)->countBy($campo)->sortDesc()->all();
    }

    private function data(?string $valor): string
    {
        return $valor ? Carbon::parse($valor)->format('d/m/Y H:i') : '—';
    }

    /** Formata horas: "5,3 h" ou "2 d 4 h". */
    public function horas(float|string|null $h, bool $comSinal = false): string
    {
        if ($h === null) {
            return '—';
        }
        $h = (float) $h;
        $sinal = $comSinal ? ($h > 0 ? '+' : ($h < 0 ? '−' : '')) : ($h < 0 ? '−' : '');
        $abs = abs($h);

        if ($abs < 24) {
            return $sinal.number_format($abs, 1, ',', '.').' h';
        }

        return $sinal.intdiv((int) round($abs), 24).' d '.((int) round($abs) % 24).' h';
    }
}
