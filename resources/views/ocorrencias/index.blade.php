@extends('layouts.app')
@section('title', 'Minhas ocorrências')

@section('content')
    <x-page-header titulo="Minhas ocorrências" subtitulo="Problemas que você apontou">
        <a href="{{ route('ocorrencias.create') }}" class="btn btn-primary"><i class="bi bi-exclamation-triangle"></i> Apontar Problema</a>
    </x-page-header>

    <ul class="nav nav-pills flex-nowrap overflow-auto mb-3 gap-1">
        <li class="nav-item">
            <a class="nav-link text-nowrap {{ request('status') ? '' : 'active' }}" href="{{ route('ocorrencias.index') }}">
                Todas <span class="badge text-bg-light">{{ $contagem->sum() }}</span>
            </a>
        </li>
        @foreach ($statuses as $s)
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ request('status') === $s->value ? 'active' : '' }}" href="{{ route('ocorrencias.index', ['status' => $s->value]) }}">
                    {{ $s->label() }} <span class="badge text-bg-light">{{ $contagem[$s->value] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        @if ($ocorrencias->isEmpty())
            <x-empty-state icone="journal-text" titulo="Nenhuma ocorrência" mensagem="Quando você apontar um problema, ele aparecerá aqui.">
                <a href="{{ route('ocorrencias.create') }}" class="btn btn-primary"><i class="bi bi-exclamation-triangle"></i> Apontar Problema</a>
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light">
                    <tr><th>Número</th><th>Título</th><th>Área / Equipamento</th><th>Criticidade</th><th>Status</th><th>Data</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($ocorrencias as $o)
                        <tr>
                            <td data-label="Número" class="fw-semibold text-nowrap">{{ $o->numero }}</td>
                            <td data-label="Título">{{ $o->titulo }}</td>
                            <td data-label="Área">{{ $o->area->codigo }}<span class="text-muted"> · {{ $o->equipamento?->tag ?? 'Estrutura' }}</span></td>
                            <td data-label="Criticidade"><x-status-badge :status="$o->criticidade" /></td>
                            <td data-label="Status">
                                <x-status-badge :status="$o->status" />
                                @if ($o->ordemServico)
                                    <div class="small text-muted">{{ $o->ordemServico->numero }} · {{ $o->ordemServico->status->label() }}</div>
                                @endif
                            </td>
                            <td data-label="Data" class="text-nowrap">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @can('update', $o)
                                        <a href="{{ route('ocorrencias.edit', $o) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Corrigir</a>
                                    @endcan
                                    <a href="{{ route('ocorrencias.show', $o) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $ocorrencias->links() }}</div>
@endsection
