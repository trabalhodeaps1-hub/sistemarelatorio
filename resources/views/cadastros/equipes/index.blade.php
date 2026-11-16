@extends('layouts.app')
@section('title', 'Equipes')

@section('content')
    <x-page-header titulo="Equipes" subtitulo="Equipes técnicas de execução (sem login)">
        <a href="{{ route('equipes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova equipe</a>
    </x-page-header>

    <x-filtro-lista placeholder="Nome ou especialidade" />

    <div class="card">
        @if ($equipes->isEmpty())
            <x-empty-state icone="person-gear" titulo="Nenhuma equipe encontrada" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>Nome</th><th>Especialidade</th><th>Membros</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($equipes as $e)
                        <tr>
                            <td data-label="Nome" class="fw-semibold">{{ $e->nome }}</td>
                            <td data-label="Especialidade">{{ $e->especialidade ?? '—' }}</td>
                            <td data-label="Membros">{{ $e->membros_count }}</td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$e->ativo" /></td>
                            <td><x-acoes-cadastro rota="equipes" :model="$e" rotulo="equipe" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $equipes->links() }}</div>
@endsection
