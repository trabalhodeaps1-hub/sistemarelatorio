@extends('layouts.app')
@section('title', 'Usuários')

@section('content')
    <x-page-header titulo="Usuários" subtitulo="Acesso ao sistema por perfil">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo usuário</a>
    </x-page-header>

    <x-filtro-lista placeholder="Nome, e-mail ou matrícula">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="role">Perfil</label>
            <select name="role" id="role" class="form-select">
                <option value="">Todos</option>
                @foreach ($roles as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('role') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
    </x-filtro-lista>

    <div class="card">
        @if ($usuarios->isEmpty())
            <x-empty-state icone="people" titulo="Nenhum usuário encontrado" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead class="table-light">
                    <tr><th>Nome</th><th>Matrícula</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($usuarios as $u)
                        <tr>
                            <td data-label="Nome" class="fw-semibold">{{ $u->name }}</td>
                            <td data-label="Matrícula">{{ $u->matricula }}</td>
                            <td data-label="E-mail" class="text-break">{{ $u->email }}</td>
                            <td data-label="Perfil"><x-status-badge :status="$u->role" /></td>
                            <td data-label="Situação"><x-ativo-badge :ativo="$u->ativo" /></td>
                            <td><x-acoes-cadastro rota="usuarios" :model="$u" rotulo="usuário" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
