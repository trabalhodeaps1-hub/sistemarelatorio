@extends('layouts.app')
@section('title', 'Fila de análise')

@section('content')
    <x-page-header titulo="Fila de análise" subtitulo="Ocorrências pendentes, das mais críticas e antigas para as mais recentes" />

    @if ($totalCriticas)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <div><strong>{{ $totalCriticas }}</strong> ocorrência(s) crítica(s) aguardando análise.</div>
        </div>
    @endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="area_id">Área</label>
                <select name="area_id" id="area_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($areas as $id => $nome)
                        <option value="{{ $id }}" @selected((string) request('area_id') === (string) $id)>{{ $nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="criticidade">Criticidade</label>
                <select name="criticidade" id="criticidade" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($criticidades as $v => $r)
                        <option value="{{ $v }}" @selected(request('criticidade') === $v)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="{{ route('analises.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    @if ($ocorrencias->isEmpty())
        <div class="card"><x-empty-state icone="check2-all" titulo="Fila vazia" mensagem="Nenhuma ocorrência aguardando análise." /></div>
    @else
        <div class="vstack gap-2">
            @foreach ($ocorrencias as $o)
                @php $critica = $o->criticidade === \App\Enums\Criticidade::Critica; @endphp
                <a href="{{ route('analises.show', $o) }}" class="card text-decoration-none text-body {{ $critica ? 'row-critica bg-danger-subtle' : '' }}">
                    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                        <div class="flex-grow-1" style="min-width: 220px">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <span class="fw-bold">{{ $o->numero }}</span>
                                <x-status-badge :status="$o->criticidade" />
                                @if ($o->reenvios)<span class="badge text-bg-light border">{{ $o->reenvios }}º reenvio</span>@endif
                                @if ($o->risco_pessoas)<span class="badge text-bg-danger"><i class="bi bi-person-exclamation"></i> Risco a pessoas</span>@endif
                                @if ($o->equipamento_parado)<span class="badge text-bg-warning"><i class="bi bi-stop-circle"></i> Parado</span>@endif
                            </div>
                            <div class="fw-semibold">{{ $o->titulo }}</div>
                            <div class="small text-muted">
                                {{ $o->area->nome }} · {{ $o->equipamento?->tag ?? 'Estrutura' }} · {{ $o->categoria->nome }}
                            </div>
                        </div>
                        <div class="small text-muted text-md-end">
                            <div><i class="bi bi-person"></i> {{ $o->user->name }}</div>
                            <div><i class="bi bi-clock"></i> {{ $o->created_at->format('d/m/Y H:i') }} ({{ $o->created_at->diffForHumans() }})</div>
                            <div><i class="bi bi-images"></i> {{ $o->fotos_count }} foto(s)</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted d-none d-md-block"></i>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-3">{{ $ocorrencias->links() }}</div>
    @endif
@endsection
