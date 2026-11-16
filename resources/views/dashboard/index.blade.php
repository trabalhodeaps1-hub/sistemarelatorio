@extends('layouts.app')

@section('title', 'Dashboard')

@php use App\Enums\Role; @endphp

@section('content')
    <x-page-header titulo="Olá, {{ strtok($user->name, ' ') }}" subtitulo="{{ $user->role->label() }} · {{ now()->translatedFormat('l, d \d\e F') }}">
        @if ($user->role === Role::Tecnico || $user->isMaster())
            <a href="{{ route('ocorrencias.create') }}" class="btn btn-primary"><i class="bi bi-exclamation-triangle"></i> Apontar Problema</a>
        @endif
    </x-page-header>

    {{-- Cards --}}
    <div class="row g-3 mb-4">
        @foreach ($cards as [$titulo, $valor, $icone, $cor, $href])
            <div class="col-6 col-md-4 {{ count($cards) > 3 ? 'col-xl' : '' }}">
                <x-stat-card :titulo="$titulo" :valor="$valor" :icone="$icone" :cor="$cor" :href="$href" />
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- Técnico --}}
        @if ($user->role === Role::Tecnico)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold text-danger"><i class="bi bi-pencil-square me-1"></i>Reprovadas — corrija e reenvie</div>
                    <div class="list-group list-group-flush">
                        @forelse ($reprovadas as $o)
                            <a href="{{ route('ocorrencias.edit', $o) }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $o->numero }} — {{ $o->titulo }}</div>
                                <div class="small text-muted">Motivo: {{ $o->ultimaAnalise?->motivo?->descricao }}</div>
                            </a>
                        @empty
                            <x-empty-state icone="emoji-smile" titulo="Nada para corrigir" />
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i>Últimas ocorrências</div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentes as $o)
                            <a href="{{ route('ocorrencias.show', $o) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                                <span>
                                    <span class="fw-semibold">{{ $o->numero }}</span> {{ $o->titulo }}
                                    <span class="d-block small text-muted">{{ $o->area->nome }} · {{ $o->created_at->diffForHumans() }}</span>
                                </span>
                                <span class="text-end">
                                    <x-status-badge :status="$o->status" />
                                    @if ($o->ordemServico)<span class="d-block small text-muted">{{ $o->ordemServico->status->label() }}</span>@endif
                                </span>
                            </a>
                        @empty
                            <x-empty-state icone="journal-text" titulo="Você ainda não apontou problemas" />
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- Supervisor --}}
        @isset($fila)
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-inbox me-1"></i>Próximas da fila</span>
                        <a href="{{ route('analises.index') }}" class="small">Ver fila completa</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($fila as $o)
                            <a href="{{ route('analises.show', $o) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2 {{ $o->criticidade === \App\Enums\Criticidade::Critica ? 'row-critica' : '' }}">
                                <span><span class="fw-semibold">{{ $o->numero }}</span> {{ $o->titulo }}
                                    <span class="d-block small text-muted">{{ $o->area->nome }} · {{ $o->user->name }} · {{ $o->created_at->diffForHumans() }}</span></span>
                                <x-status-badge :status="$o->criticidade" />
                            </a>
                        @empty
                            <x-empty-state icone="check2-all" titulo="Fila vazia" />
                        @endforelse
                    </div>
                </div>
            </div>
        @endisset

        {{-- Gerente / Master --}}
        @isset($atrasadas)
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-danger"><i class="bi bi-alarm me-1"></i>OS mais atrasadas</span>
                        <a href="{{ route('ordens-servico.index', ['situacao' => 'atrasadas']) }}" class="small">Ver todas</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($atrasadas as $os)
                            <a href="{{ route('ordens-servico.show', $os) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2">
                                <span><span class="fw-semibold">{{ $os->numero }}</span> {{ $os->ocorrencia->titulo }}
                                    <span class="d-block small text-muted">{{ $os->ocorrencia->area->nome }} · {{ $os->equipe?->nome ?? 'sem equipe' }}</span></span>
                                <span class="text-end small text-danger text-nowrap">venceu {{ $os->prazo_previsto->diffForHumans() }}</span>
                            </a>
                        @empty
                            <x-empty-state icone="emoji-smile" titulo="Nenhuma OS atrasada" />
                        @endforelse
                    </div>
                </div>
            </div>
        @endisset

        {{-- Gráficos (supervisor, gerente, master) --}}
        @isset($graficos)
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">Ocorrências por mês</div>
                        <div class="small text-muted">{{ $graficos['periodo'] }}</div>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 260px">
                            <canvas id="graficoMeses" role="img" aria-label="Gráfico de barras: ocorrências registradas por mês"></canvas>
                        </div>
                        <details class="small mt-2">
                            <summary class="text-muted">Ver dados em tabela</summary>
                            <table class="table table-sm mt-2 mb-0">
                                <thead><tr><th>Mês</th><th class="text-end">Ocorrências</th></tr></thead>
                                <tbody>
                                @foreach ($graficos['meses'] as $m)
                                    <tr><td>{{ $m['rotulo'] }}</td><td class="text-end">{{ $m['total'] }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </details>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">Top 5 equipamentos com mais ocorrências</div>
                        <div class="small text-muted">{{ $graficos['periodo'] }}</div>
                    </div>
                    <div class="card-body">
                        @if ($graficos['equipamentos']->isEmpty())
                            <x-empty-state icone="cpu" titulo="Sem ocorrências em equipamentos no período" />
                        @else
                            <div style="position: relative; height: 260px">
                                <canvas id="graficoEquipamentos" role="img" aria-label="Gráfico de barras horizontais: equipamentos com mais ocorrências"></canvas>
                            </div>
                            <details class="small mt-2">
                                <summary class="text-muted">Ver dados em tabela</summary>
                                <table class="table table-sm mt-2 mb-0">
                                    <thead><tr><th>Equipamento</th><th class="text-end">Ocorrências</th></tr></thead>
                                    <tbody>
                                    @foreach ($graficos['equipamentos'] as $e)
                                        <tr><td>{{ $e->tag }} — {{ $e->nome }}</td><td class="text-end">{{ $e->total }}</td></tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif
                    </div>
                </div>
            </div>
        @endisset
    </div>
@endsection

@isset($graficos)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" integrity="sha384-vsrfeLOOY6KuIYKDlmVH5UiBmgIdB1oEf7p01YgWHuqmOHfZr374+odEv96n9tNC" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Chart) return;

        // Uma série por gráfico: cor única (primary), sem legenda (o título nomeia a série),
        // barras finas com 4px de raio na ponta, grade e eixos recessivos, tooltip no hover.
        const COR = '#0078DC';
        const TINTA = '#6c757d';
        const GRADE = 'rgba(0,0,0,.06)';
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = TINTA;

        const base = (indexAxis) => ({
            indexAxis,
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { displayColors: false, padding: 10, callbacks: { label: (c) => `${c.formattedValue} ocorrência(s)` } },
            },
            interaction: { mode: 'index', intersect: false },
            scales: {
                [indexAxis === 'y' ? 'x' : 'y']: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: GRADE }, border: { display: false } },
                [indexAxis === 'y' ? 'y' : 'x']: { grid: { display: false }, border: { color: GRADE } },
            },
        });

        const barra = { backgroundColor: COR, hoverBackgroundColor: '#005fae', borderRadius: 4, borderSkipped: 'start', maxBarThickness: 28 };

        const meses = @json($graficos['meses']);
        new Chart(document.getElementById('graficoMeses'), {
            type: 'bar',
            data: { labels: meses.map((m) => m.rotulo), datasets: [{ label: 'Ocorrências', data: meses.map((m) => m.total), ...barra }] },
            options: base('x'),
        });

        const equip = @json($graficos['equipamentos']);
        const el = document.getElementById('graficoEquipamentos');
        if (el) {
            const opcoes = base('y');
            opcoes.plugins.tooltip.callbacks.title = (itens) => { const e = equip[itens[0].dataIndex]; return `${e.tag} — ${e.nome}`; };
            new Chart(el, {
                type: 'bar',
                data: { labels: equip.map((e) => e.tag), datasets: [{ label: 'Ocorrências', data: equip.map((e) => e.total), ...barra, maxBarThickness: 22 }] },
                options: opcoes,
            });
        }
    });
    </script>
    @endpush
@endisset
