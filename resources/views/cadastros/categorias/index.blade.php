@extends('layouts.app')
@section('title', 'Categorias')

@section('content')
    <x-page-header titulo="Categorias" subtitulo="Tipos de ocorrência">
        <a href="{{ route('categorias.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova categoria</a>
    </x-page-header>

    <x-filtro-lista placeholder="Nome" />

    <div class="card">
        @if ($categorias->isEmpty())
            <x-empty-state icone="tags" titulo="Nenhuma categoria encontrada" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>Nome</th><th>Ocorrências</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($categorias as $c)
                        <tr>
                            <td data-label="Nome" class="fw-semibold">{{ $c->nome }}</td>
                            <td data-label="Ocorrências">{{ $c->ocorrencias_count }}</td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$c->ativo" /></td>
                            <td><x-acoes-cadastro rota="categorias" :model="$c" rotulo="categoria" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $categorias->links() }}</div>
@endsection
