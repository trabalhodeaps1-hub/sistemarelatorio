@extends('layouts.app')
@section('title', 'Motivos de reprovação')

@section('content')
    <x-page-header titulo="Motivos de reprovação" subtitulo="Usados pelo supervisor ao reprovar ocorrências">
        <a href="{{ route('motivos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo motivo</a>
    </x-page-header>

    <x-filtro-lista placeholder="Descrição" />

    <div class="card">
        @if ($motivos->isEmpty())
            <x-empty-state icone="x-octagon" titulo="Nenhum motivo encontrado" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>Ordem</th><th>Descrição</th><th>Exige comentário</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($motivos as $m)
                        <tr>
                            <td data-label="Ordem">{{ $m->ordem }}</td>
                            <td data-label="Descrição" class="fw-semibold">{{ $m->descricao }}</td>
                            <td data-label="Exige comentário">
                                @if ($m->exige_comentario)<span class="badge text-bg-warning">Sim</span>@else<span class="text-muted">Não</span>@endif
                            </td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$m->ativo" /></td>
                            <td><x-acoes-cadastro rota="motivos" :model="$m" rotulo="motivo" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $motivos->links() }}</div>
@endsection
