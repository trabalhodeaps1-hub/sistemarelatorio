@extends('layouts.app')
@section('title', 'Áreas')

@section('content')
    <x-page-header titulo="Áreas" subtitulo="Setores da planta">
        <a href="{{ route('areas.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova área</a>
    </x-page-header>

    <x-filtro-lista placeholder="Código ou nome" />

    <div class="card">
        @if ($areas->isEmpty())
            <x-empty-state icone="diagram-3" titulo="Nenhuma área encontrada" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>Código</th><th>Nome</th><th>Equipamentos</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($areas as $a)
                        <tr>
                            <td data-label="Código" class="fw-semibold">{{ $a->codigo }}</td>
                            <td data-label="Nome">{{ $a->nome }}<div class="small text-muted d-none d-md-block">{{ $a->descricao }}</div></td>
                            <td data-label="Equipamentos"><a href="{{ route('equipamentos.index', ['area_id' => $a->id]) }}">{{ $a->equipamentos_count }}</a></td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$a->ativo" /></td>
                            <td><x-acoes-cadastro rota="areas" :model="$a" rotulo="área" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $areas->links() }}</div>
@endsection
