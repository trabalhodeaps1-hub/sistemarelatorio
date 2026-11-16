@extends('layouts.app')
@section('title', 'Equipamentos')

@section('content')
    <x-page-header titulo="Equipamentos" subtitulo="Ativos físicos por área">
        <a href="{{ route('equipamentos.create', ['area_id' => request('area_id')]) }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo equipamento</a>
    </x-page-header>

    <x-filtro-lista placeholder="TAG ou nome">
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1" for="area_id">Área</label>
            <select name="area_id" id="area_id" class="form-select">
                <option value="">Todas</option>
                @foreach ($areas as $id => $nome)
                    <option value="{{ $id }}" @selected((string) request('area_id') === (string) $id)>{{ $nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="criticidade">Criticidade</label>
            <select name="criticidade" id="criticidade" class="form-select">
                <option value="">Todas</option>
                @foreach ($criticidades as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('criticidade') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
    </x-filtro-lista>

    <div class="card">
        @if ($equipamentos->isEmpty())
            <x-empty-state icone="cpu" titulo="Nenhum equipamento encontrado" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>TAG</th><th>Nome</th><th>Área</th><th>Criticidade</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($equipamentos as $e)
                        <tr>
                            <td data-label="TAG" class="fw-semibold font-monospace">{{ $e->tag }}</td>
                            <td data-label="Nome">{{ $e->nome }}</td>
                            <td data-label="Área">{{ $e->area?->codigo }}</td>
                            <td data-label="Criticidade"><x-status-badge :status="$e->criticidade" /></td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$e->ativo" /></td>
                            <td><x-acoes-cadastro rota="equipamentos" :model="$e" rotulo="equipamento" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $equipamentos->links() }}</div>
@endsection
