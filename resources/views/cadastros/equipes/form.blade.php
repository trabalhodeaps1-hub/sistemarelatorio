@extends('layouts.app')
@section('title', $equipe->exists ? 'Editar equipe' : 'Nova equipe')

@php
    $membros = old('membros', $equipe->exists ? $equipe->membros->map->only(['nome', 'matricula'])->all() : []);
    if (empty($membros)) {
        $membros = [['nome' => '', 'matricula' => '']];
    }
@endphp

@section('content')
    <x-page-header :titulo="$equipe->exists ? 'Editar equipe' : 'Nova equipe'">
        <a href="{{ route('equipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <form method="POST" action="{{ $equipe->exists ? route('equipes.update', $equipe) : route('equipes.store') }}" class="card" style="max-width: 820px">
        @csrf
        @if ($equipe->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><x-form.input name="nome" label="Nome" :value="$equipe->nome" required /></div>
                <div class="col-md-6"><x-form.input name="especialidade" label="Especialidade" :value="$equipe->especialidade" /></div>
            </div>
            <x-form.checkbox name="ativo" label="Equipe ativa" :checked="$equipe->ativo" />

            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Membros</h2>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-membro"><i class="bi bi-plus-lg"></i> Adicionar membro</button>
            </div>
            <div id="membros">
                @foreach ($membros as $i => $m)
                    <div class="row g-2 mb-2 membro-linha">
                        <div class="col-7">
                            <input type="text" name="membros[{{ $i }}][nome]" value="{{ $m['nome'] ?? '' }}" placeholder="Nome"
                                   class="form-control @error("membros.$i.nome") is-invalid @enderror">
                            @error("membros.$i.nome")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col">
                            <input type="text" name="membros[{{ $i }}][matricula]" value="{{ $m['matricula'] ?? '' }}" placeholder="Matrícula"
                                   class="form-control @error("membros.$i.matricula") is-invalid @enderror">
                            @error("membros.$i.matricula")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-remover-membro" title="Remover"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lista = document.getElementById('membros');
    let indice = lista.querySelectorAll('.membro-linha').length;

    document.getElementById('add-membro').addEventListener('click', () => {
        const modelo = lista.querySelector('.membro-linha').cloneNode(true);
        modelo.querySelectorAll('input').forEach((input) => {
            input.value = '';
            input.classList.remove('is-invalid');
            input.name = input.name.replace(/membros\[\d+\]/, `membros[${indice}]`);
        });
        modelo.querySelectorAll('.invalid-feedback').forEach((el) => el.remove());
        lista.appendChild(modelo);
        indice++;
    });

    lista.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remover-membro');
        if (!btn) return;
        const linhas = lista.querySelectorAll('.membro-linha');
        if (linhas.length > 1) btn.closest('.membro-linha').remove();
        else btn.closest('.membro-linha').querySelectorAll('input').forEach((i) => (i.value = ''));
    });
});
</script>
@endpush
