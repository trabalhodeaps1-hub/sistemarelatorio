@extends('layouts.app')
@section('title', 'Analisar '.$ocorrencia->numero)

@section('content')
    <x-page-header :titulo="'Analisar '.$ocorrencia->numero" subtitulo="Aprove para gerar a OS ou reprove para devolver ao técnico">
        <a href="{{ route('analises.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Fila</a>
    </x-page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('ocorrencias._detalhes')
            @include('ocorrencias._galeria', ['fotos' => $ocorrencia->fotos, 'titulo' => 'Fotos do problema'])
            @if ($ocorrencia->analises->isNotEmpty())
                @include('ocorrencias._analises')
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4 border-success border-top border-4">
                <div class="card-header bg-white fw-semibold text-success"><i class="bi bi-check-circle me-1"></i>Aprovar</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('analises.aprovar', $ocorrencia) }}" data-confirm="Aprovar e gerar a OS?">
                        @csrf
                        <x-form.select name="criticidade" label="Criticidade final" :options="$criticidades" :value="$ocorrencia->criticidade" :placeholder="false"
                                       help="Ajuste se necessário. Define o prazo (SLA) da OS." required />
                        <x-form.textarea name="comentario" id="comentario_aprovar" label="Comentário (opcional)" rows="2" />
                        <button class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Aprovar e gerar OS</button>
                    </form>
                </div>
            </div>

            <div class="card border-danger border-top border-4">
                <div class="card-header bg-white fw-semibold text-danger"><i class="bi bi-x-circle me-1"></i>Reprovar</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('analises.reprovar', $ocorrencia) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="motivo_reprovacao_id" class="form-label">Motivo<span class="text-danger">*</span></label>
                            <select name="motivo_reprovacao_id" id="motivo_reprovacao_id" required
                                    class="form-select @error('motivo_reprovacao_id') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @foreach ($motivos as $m)
                                    <option value="{{ $m->id }}" data-exige="{{ $m->exige_comentario ? 1 : 0 }}"
                                        @selected((string) old('motivo_reprovacao_id') === (string) $m->id)>
                                        {{ $m->descricao }}{{ $m->exige_comentario ? ' *' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('motivo_reprovacao_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">* exige comentário</div>
                        </div>
                        <div class="mb-3">
                            <label for="comentario_reprovar" class="form-label">Comentário <span id="comentario-obrigatorio" class="text-danger d-none">*</span></label>
                            <textarea name="comentario" id="comentario_reprovar" rows="3"
                                      class="form-control @error('comentario') is-invalid @enderror"
                                      placeholder="Oriente o técnico sobre o que corrigir">{{ old('comentario') }}</textarea>
                            @error('comentario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg"></i> Reprovar e devolver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('motivo_reprovacao_id');
    const comentario = document.getElementById('comentario_reprovar');
    const marca = document.getElementById('comentario-obrigatorio');
    const atualizar = () => {
        const exige = select.selectedOptions[0]?.dataset.exige === '1';
        comentario.required = exige;
        marca.classList.toggle('d-none', !exige);
    };
    select.addEventListener('change', atualizar);
    atualizar();
});
</script>
@endpush
