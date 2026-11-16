@extends('layouts.app')
@section('title', $categoria->exists ? 'Editar categoria' : 'Nova categoria')

@section('content')
    <x-page-header :titulo="$categoria->exists ? 'Editar categoria' : 'Nova categoria'">
        <a href="{{ route('categorias.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}" class="card" style="max-width: 640px">
        @csrf
        @if ($categoria->exists) @method('PUT') @endif
        <div class="card-body">
            <x-form.input name="nome" label="Nome" :value="$categoria->nome" required />
            <x-form.checkbox name="ativo" label="Categoria ativa" :checked="$categoria->ativo" />
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
@endsection
