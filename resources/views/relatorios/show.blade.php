@extends('layouts.app')
@section('title', $relatorio->titulo)

@section('content')
    <x-page-header :titulo="$relatorio->titulo" :subtitulo="$relatorio->descricao">
        <a href="{{ route('relatorios.index', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Relatórios</a>
    </x-page-header>

    @include('relatorios._filtros', [
        'acao' => route('relatorios.show', $tipo),
        'botoes' => '<button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Atualizar prévia</button>'
            .'<button type="submit" formaction="'.e(route('relatorios.pdf', $tipo)).'" formtarget="_blank" class="btn btn-dark"><i class="bi bi-file-earmark-pdf"></i> Gerar PDF</button>',
    ])

    <ul class="nav nav-pills flex-nowrap overflow-auto mb-3 gap-1 small">
        @foreach ($tipos as $chave => [$titulo])
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ $chave === $tipo ? 'active' : '' }}" href="{{ route('relatorios.show', ['tipo' => $chave, ...request()->query()]) }}">{{ $titulo }}</a>
            </li>
        @endforeach
    </ul>

    @if ($relatorio->resumo)
        <div class="row g-3 mb-3">
            @foreach ($relatorio->resumo as $rotulo => $valor)
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card h-100"><div class="card-body py-2">
                        <div class="small text-muted text-truncate" title="{{ $rotulo }}">{{ $rotulo }}</div>
                        <div class="fs-5 fw-bold">{{ $valor }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-white small text-muted">
            @foreach ($filtrosDescritos as $r => $v)<span class="me-3"><strong>{{ $r }}:</strong> {{ $v }}</span>@endforeach
            <span class="float-md-end">{{ count($relatorio->linhas) }} linha(s)</span>
        </div>
        @include('relatorios._tabela')
    </div>
@endsection
