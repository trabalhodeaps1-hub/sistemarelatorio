@extends('layouts.app')
@section('title', 'Ordens de serviço')

@section('content')
    <x-page-header titulo="Ordens de serviço" subtitulo="Programação e acompanhamento da execução" />

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <x-stat-card titulo="Atrasadas" :valor="$contadores['atrasadas']" icone="alarm" cor="danger" :href="route('ordens-servico.index', ['situacao' => 'atrasadas'])" />
        </div>
        <div class="col-6 col-md-4">
            <x-stat-card titulo="Vencem em 24h" :valor="$contadores['vencendo']" icone="hourglass-split" cor="warning" :href="route('ordens-servico.index', ['situacao' => 'vencendo'])" />
        </div>
        <div class="col-6 col-md-4">
            <x-stat-card titulo="Em andamento" :valor="$contadores['andamento']" icone="tools" cor="primary" :href="route('ordens-servico.index', ['situacao' => 'andamento'])" />
        </div>
    </div>

    <form method="GET" class="card card-body mb-3">
        <input type="hidden" name="situacao" value="{{ request('situacao') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="busca">Busca</label>
                <input type="search" name="busca" id="busca" class="form-control" value="{{ request('busca') }}" placeholder="Nº da OS/OC ou título">
            </div>
            @foreach (['status' => ['Status', $statuses], 'area_id' => ['Área', $areas], 'equipe_id' => ['Equipe', $equipes], 'criticidade' => ['Criticidade', $criticidades]] as $campo => [$rotulo, $opcoes])
                <div class="col-6 col-md">
                    <label class="form-label small mb-1" for="{{ $campo }}">{{ $rotulo }}</label>
                    <select name="{{ $campo }}" id="{{ $campo }}" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($opcoes as $v => $r)
                            <option value="{{ $v }}" @selected((string) request($campo) === (string) $v)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="{{ route('ordens-servico.index') }}" class="btn btn-outline-secondary" title="Limpar"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
        @if (request('situacao'))
            <div class="small mt-2">Filtro rápido: <strong>{{ ['atrasadas' => 'Atrasadas', 'vencendo' => 'Vencem em 24h', 'andamento' => 'Em andamento'][request('situacao')] ?? '' }}</strong>
                · <a href="{{ request()->fullUrlWithQuery(['situacao' => null]) }}">remover</a></div>
        @endif
    </form>

    <div class="card">
        @if ($ordens->isEmpty())
            <x-empty-state icone="tools" titulo="Nenhuma OS encontrada" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light">
                    <tr><th>OS</th><th>Problema</th><th>Área</th><th>Criticidade</th><th>Equipe</th><th>Prazo</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($ordens as $os)
                        <tr class="{{ $os->atrasada() && ! $os->status->finalizada() ? 'table-danger' : '' }}">
                            <td data-label="OS" class="fw-semibold text-nowrap">{{ $os->numero }}</td>
                            <td data-label="Problema">
                                {{ $os->ocorrencia->titulo }}
                                <div class="small text-muted">{{ $os->ocorrencia->numero }} · {{ $os->ocorrencia->equipamento?->tag ?? 'Estrutura' }}</div>
                            </td>
                            <td data-label="Área">{{ $os->ocorrencia->area->codigo }}</td>
                            <td data-label="Criticidade"><x-status-badge :status="$os->criticidade" /></td>
                            <td data-label="Equipe">{{ $os->equipe?->nome ?? '—' }}</td>
                            <td data-label="Prazo" class="text-nowrap">
                                {{ $os->prazo_previsto?->format('d/m/Y H:i') }}
                                <div><x-prazo-badge :os="$os" /></div>
                            </td>
                            <td data-label="Status"><x-status-badge :status="$os->status" /></td>
                            <td class="text-end"><a href="{{ route('ordens-servico.show', $os) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Abrir</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $ordens->links() }}</div>
@endsection
