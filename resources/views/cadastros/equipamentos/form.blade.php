@extends('layouts.app')
@section('title', $equipamento->exists ? 'Editar equipamento' : 'Novo equipamento')

@section('content')
    <x-page-header :titulo="$equipamento->exists ? 'Editar equipamento' : 'Novo equipamento'">
        <a href="{{ route('equipamentos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <form method="POST" action="{{ $equipamento->exists ? route('equipamentos.update', $equipamento) : route('equipamentos.store') }}" class="card" style="max-width: 720px">
        @csrf
        @if ($equipamento->exists) @method('PUT') @endif
        <div class="card-body">
            <x-form.select name="area_id" label="Área" :options="$areas" :value="$equipamento->area_id" required />
            <div class="row">
                <div class="col-md-4"><x-form.input name="tag" label="TAG" :value="$equipamento->tag" maxlength="40" required /></div>
                <div class="col-md-8"><x-form.input name="nome" label="Nome" :value="$equipamento->nome" required /></div>
            </div>
            <x-form.select name="criticidade" label="Criticidade" :options="$criticidades" :value="$equipamento->criticidade" required />
            <x-form.checkbox name="ativo" label="Equipamento ativo" :checked="$equipamento->ativo" />
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
@endsection
