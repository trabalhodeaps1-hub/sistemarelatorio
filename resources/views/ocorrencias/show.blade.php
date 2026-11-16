@extends('layouts.app')
@section('title', $ocorrencia->numero)

@section('content')
    <x-page-header :titulo="'Ocorrência '.$ocorrencia->numero">
        @can('analisar', $ocorrencia)
            <a href="{{ route('analises.show', $ocorrencia) }}" class="btn btn-primary"><i class="bi bi-clipboard-check"></i> Analisar</a>
        @endcan
        @can('update', $ocorrencia)
            <a href="{{ route('ocorrencias.edit', $ocorrencia) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Corrigir e reenviar</a>
        @endcan
        @can('cancelar', $ocorrencia)
            <form method="POST" action="{{ route('ocorrencias.cancelar', $ocorrencia) }}" data-confirm="Cancelar esta ocorrência? Esta ação não pode ser desfeita.">
                @csrf @method('PATCH')
                <button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Cancelar</button>
            </form>
        @endcan
    </x-page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('ocorrencias._detalhes')
            @include('ocorrencias._galeria', ['fotos' => $ocorrencia->fotos->where('tipo', \App\Enums\TipoFoto::Antes), 'titulo' => 'Fotos do problema'])
            @php $depois = $ocorrencia->fotos->where('tipo', \App\Enums\TipoFoto::Depois); @endphp
            @if ($depois->isNotEmpty())
                @include('ocorrencias._galeria', ['fotos' => $depois, 'titulo' => 'Fotos depois do reparo'])
            @endif
        </div>
        <div class="col-12 col-xl-4">
            @if ($os = $ocorrencia->ordemServico)
                <div class="card mb-4 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">{{ $os->numero }}</span>
                            <x-status-badge :status="$os->status" />
                        </div>
                        <dl class="row small mb-0">
                            <dt class="col-5">Equipe</dt><dd class="col-7">{{ $os->equipe?->nome ?? 'A definir' }}</dd>
                            <dt class="col-5">Prazo</dt><dd class="col-7">{{ $os->prazo_previsto?->format('d/m/Y H:i') ?? '—' }}</dd>
                            @if ($os->concluida_em)
                                <dt class="col-5">Concluída</dt><dd class="col-7">{{ $os->concluida_em->format('d/m/Y H:i') }}</dd>
                            @endif
                        </dl>
                        @if (Route::has('ordens-servico.show') && auth()->user()->can('view', $os))
                            <a href="{{ route('ordens-servico.show', $os) }}" class="btn btn-sm btn-outline-primary mt-2">Abrir OS</a>
                        @endif
                    </div>
                </div>
            @endif
            @include('ocorrencias._analises')
        </div>
    </div>
@endsection
