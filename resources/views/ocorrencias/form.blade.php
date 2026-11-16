@extends('layouts.app')
@php $editando = $ocorrencia->exists; @endphp
@section('title', $editando ? 'Corrigir ocorrência' : 'Apontar Problema')

@section('content')
    <x-page-header :titulo="$editando ? 'Corrigir e reenviar '.$ocorrencia->numero : 'Apontar Problema'"
                   :subtitulo="$editando ? 'Ajuste os pontos indicados pelo supervisor' : 'Registre o problema encontrado em campo'">
        <a href="{{ $editando ? route('ocorrencias.show', $ocorrencia) : route('ocorrencias.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    @if ($editando && $ocorrencia->ultimaAnalise)
        <div class="alert alert-danger">
            <div class="fw-semibold"><i class="bi bi-x-octagon me-1"></i>Reprovada por {{ $ocorrencia->ultimaAnalise->user->name }} em {{ $ocorrencia->ultimaAnalise->created_at->format('d/m/Y H:i') }}</div>
            <div><strong>Motivo:</strong> {{ $ocorrencia->ultimaAnalise->motivo?->descricao }}</div>
            @if ($ocorrencia->ultimaAnalise->comentario)
                <div style="white-space: pre-line">{{ $ocorrencia->ultimaAnalise->comentario }}</div>
            @endif
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="form-ocorrencia" style="max-width: 820px"
          action="{{ $editando ? route('ocorrencias.update', $ocorrencia) : route('ocorrencias.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">1. Onde é o problema?</div>
            <div class="card-body">
                <x-form.select name="area_id" label="Área" :options="$areas" :value="$ocorrencia->area_id" required
                               data-equipamentos-url="{{ url('/areas/__AREA__/equipamentos') }}" />

                <div class="mb-3">
                    <label for="equipamento_id" class="form-label">Equipamento<span class="text-danger">*</span></label>
                    @php $equipSel = old('equipamento_id', $editando ? ($ocorrencia->equipamento_id ?? 0) : ''); @endphp
                    <select name="equipamento_id" id="equipamento_id" required data-selecionado="{{ $equipSel }}"
                            class="form-select @error('equipamento_id') is-invalid @enderror">
                        <option value="">Selecione a área primeiro</option>
                    </select>
                    @error('equipamento_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Para pisos, escadas, guarda-corpos etc., escolha "Estrutura / Não se aplica".</div>
                </div>

                <x-form.input name="local" label="Local (referência)" :value="$ocorrencia->local" placeholder="Ex.: plataforma do 2º andar, lado norte" maxlength="255" />
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">2. O que está acontecendo?</div>
            <div class="card-body">
                <x-form.select name="categoria_id" label="Categoria" :options="$categorias" :value="$ocorrencia->categoria_id" required />
                <x-form.input name="titulo" label="Título" :value="$ocorrencia->titulo" maxlength="120" placeholder="Resumo curto do problema" required />
                <x-form.textarea name="descricao" label="Descrição" :value="$ocorrencia->descricao" rows="5" required minlength="30"
                                 help="Mínimo de 30 caracteres. Descreva o que viu, desde quando e o impacto." />
                <x-form.select name="criticidade_sugerida" label="Criticidade sugerida" :options="$criticidades" :value="$ocorrencia->criticidade_sugerida" required />

                <x-form.checkbox name="risco_pessoas" label="Há risco a pessoas" :checked="$ocorrencia->risco_pessoas" />
                <x-form.checkbox name="equipamento_parado" label="Equipamento parado" :checked="$ocorrencia->equipamento_parado" />

                <x-form.textarea name="observacoes" label="Observações" :value="$ocorrencia->observacoes" rows="2" />
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">3. Fotos <span class="text-muted fw-normal small">(1 a {{ $maxFotos }}, JPG/PNG/WEBP até 10 MB)</span></div>
            <div class="card-body">
                @if ($editando && $ocorrencia->fotosAntes->isNotEmpty())
                    <div class="mb-3">
                        <div class="small text-muted mb-2">Fotos atuais (marque para remover):</div>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($ocorrencia->fotosAntes as $foto)
                                <label class="text-center small">
                                    <img src="{{ $foto->url() }}" class="foto-thumb d-block mb-1" alt="Foto">
                                    <input type="checkbox" class="form-check-input remover-foto" name="remover_fotos[]" value="{{ $foto->id }}"> remover
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <input type="file" name="fotos[]" id="fotos" accept="image/*" capture="environment" multiple
                       class="d-none" data-max="{{ $maxFotos }}" data-existentes="{{ $editando ? $ocorrencia->fotosAntes->count() : 0 }}" data-max-mb="10">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-outline-primary" id="btn-camera"><i class="bi bi-camera"></i> Tirar / escolher foto</button>
                </div>
                <div id="fotos-preview" class="d-flex flex-wrap gap-3 mt-2"></div>
                <div id="fotos-aviso" class="small text-danger mt-2"></div>
                @error('fotos')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @foreach ($errors->get('fotos.*') as $mensagens)
                    <div class="text-danger small">{{ $mensagens[0] }}</div>
                @endforeach
            </div>
        </div>

        <div class="d-grid d-md-flex justify-content-md-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-send"></i> {{ $editando ? 'Corrigir e reenviar' : 'Enviar para análise' }}
            </button>
        </div>
    </form>
@endsection
