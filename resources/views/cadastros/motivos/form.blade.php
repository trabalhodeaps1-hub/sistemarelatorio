@extends('layouts.app')
@section('title', $motivo->exists ? 'Editar motivo' : 'Novo motivo')

@section('content')
    <x-page-header :titulo="$motivo->exists ? 'Editar motivo de reprovação' : 'Novo motivo de reprovação'">
        <a href="{{ route('motivos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <form method="POST" action="{{ $motivo->exists ? route('motivos.update', $motivo) : route('motivos.store') }}" class="card" style="max-width: 640px">
        @csrf
        @if ($motivo->exists) @method('PUT') @endif
        <div class="card-body">
            <x-form.input name="descricao" label="Descrição" :value="$motivo->descricao" required />
            <x-form.input name="ordem" type="number" label="Ordem de exibição" :value="$motivo->ordem" min="0" max="999" required />
            <x-form.checkbox name="exige_comentario" label="Exige comentário do supervisor" :checked="$motivo->exige_comentario" />
            <x-form.checkbox name="ativo" label="Motivo ativo" :checked="$motivo->ativo" />
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
@endsection
