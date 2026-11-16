@extends('layouts.app')
@section('title', 'Notificações')

@php $icones = ['info' => 'info-circle text-info', 'alerta' => 'exclamation-triangle text-warning', 'critico' => 'exclamation-octagon text-danger']; @endphp

@section('content')
    <x-page-header titulo="Notificações">
        <form method="POST" action="{{ route('notificacoes.ler-todas') }}">
            @csrf
            <button class="btn btn-outline-primary"><i class="bi bi-check2-all"></i> Marcar todas como lidas</button>
        </form>
    </x-page-header>

    <ul class="nav nav-pills mb-3 gap-1">
        <li class="nav-item"><a class="nav-link {{ request('filtro') ? '' : 'active' }}" href="{{ route('notificacoes.index') }}">Todas</a></li>
        <li class="nav-item"><a class="nav-link {{ request('filtro') === 'nao-lidas' ? 'active' : '' }}" href="{{ route('notificacoes.index', ['filtro' => 'nao-lidas']) }}">Não lidas</a></li>
    </ul>

    <div class="card">
        @if ($notificacoes->isEmpty())
            <x-empty-state icone="bell-slash" titulo="Nenhuma notificação" />
        @else
            <div class="list-group list-group-flush">
                @foreach ($notificacoes as $n)
                    <div class="list-group-item d-flex gap-3 align-items-start {{ $n->read_at ? '' : 'bg-light' }}">
                        <i class="bi bi-{{ $icones[$n->data['nivel'] ?? 'info'] ?? $icones['info'] }} fs-5 mt-1"></i>
                        <a href="{{ route('notificacoes.abrir', $n->id) }}" class="flex-grow-1 text-decoration-none text-body">
                            <div class="fw-semibold">{{ $n->data['titulo'] ?? 'Notificação' }}
                                @unless ($n->read_at)<span class="badge text-bg-primary ms-1">nova</span>@endunless
                            </div>
                            <div class="small text-muted">{{ $n->data['mensagem'] ?? '' }}</div>
                            <div class="small text-muted">{{ $n->created_at->format('d/m/Y H:i') }} · {{ $n->created_at->diffForHumans() }}</div>
                        </a>
                        @unless ($n->read_at)
                            <form method="POST" action="{{ route('notificacoes.ler', $n->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" title="Marcar como lida"><i class="bi bi-check2"></i></button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $notificacoes->links() }}</div>
@endsection
