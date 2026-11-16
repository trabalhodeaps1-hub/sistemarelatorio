@extends('layouts.app')
@section('title', $area->exists ? 'Editar área' : 'Nova área')

@section('content')
    <x-page-header :titulo="$area->exists ? 'Editar área' : 'Nova área'">
        <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <form method="POST" action="{{ $area->exists ? route('areas.update', $area) : route('areas.store') }}" class="card" style="max-width: 720px">
        @csrf
        @if ($area->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><x-form.input name="codigo" label="Código" :value="$area->codigo" maxlength="20" required /></div>
                <div class="col-md-8"><x-form.input name="nome" label="Nome" :value="$area->nome" required /></div>
            </div>
            <x-form.textarea name="descricao" label="Descrição" :value="$area->descricao" rows="3" />
            <x-form.checkbox name="ativo" label="Área ativa" :checked="$area->ativo" />
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
@endsection
