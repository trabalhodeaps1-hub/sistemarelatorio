@extends('layouts.app')
@section('title', $os->numero)

@php
    use App\Enums\StatusOS;
    use App\Enums\TipoFoto;
    $cores = [
        StatusOS::Programada->value => 'primary', StatusOS::EmExecucao->value => 'warning', StatusOS::Pausada->value => 'secondary',
        StatusOS::Concluida->value => 'success', StatusOS::Cancelada->value => 'outline-danger',
    ];
    $icones = [
        StatusOS::Programada->value => 'calendar-check', StatusOS::EmExecucao->value => 'play-fill', StatusOS::Pausada->value => 'pause-fill',
        StatusOS::Concluida->value => 'check2-circle', StatusOS::Cancelada->value => 'x-circle',
    ];
    $modalComErro = old('status');
@endphp

@section('content')
    <x-page-header :titulo="'Ordem de serviço '.$os->numero">
        <a href="{{ route('ordens-servico.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Lista</a>
        @if (Route::has('relatorios.ficha-os'))
            <a href="{{ route('relatorios.ficha-os', $os) }}" class="btn btn-outline-dark" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Ficha PDF</a>
        @endif
    </x-page-header>

    {{-- Resumo + ações --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <x-status-badge :status="$os->status" class="fs-6" />
                        <x-status-badge :status="$os->criticidade" />
                        <x-prazo-badge :os="$os" />
                    </div>
                    <dl class="row small mb-0">
                        <dt class="col-5 col-md-3">Prazo previsto</dt><dd class="col-7 col-md-3">{{ $os->prazo_previsto?->format('d/m/Y H:i') ?? '—' }}</dd>
                        <dt class="col-5 col-md-3">Equipe</dt><dd class="col-7 col-md-3">{{ $os->equipe?->nome ?? 'A definir' }}</dd>
                        <dt class="col-5 col-md-3">Aberta em</dt><dd class="col-7 col-md-3">{{ $os->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-5 col-md-3">Responsável</dt><dd class="col-7 col-md-3">{{ $os->responsavel?->name ?? '—' }}</dd>
                        @if ($os->iniciada_em)<dt class="col-5 col-md-3">Iniciada em</dt><dd class="col-7 col-md-3">{{ $os->iniciada_em->format('d/m/Y H:i') }}</dd>@endif
                        @if ($os->concluida_em)<dt class="col-5 col-md-3">Concluída em</dt><dd class="col-7 col-md-3">{{ $os->concluida_em->format('d/m/Y H:i') }}</dd>@endif
                        @if ($os->cancelada_em)<dt class="col-5 col-md-3">Cancelada em</dt><dd class="col-7 col-md-3">{{ $os->cancelada_em->format('d/m/Y H:i') }}</dd>@endif
                    </dl>
                </div>
                @can('transicionar', $os)
                    @if ($transicoes)
                        <div class="col-12 col-lg-auto d-grid d-md-flex gap-2">
                            @foreach ($transicoes as $t)
                                <button type="button" class="btn btn-{{ $cores[$t->value] ?? 'primary' }}" data-bs-toggle="modal" data-bs-target="#modal-{{ $t->value }}">
                                    <i class="bi bi-{{ $icones[$t->value] ?? 'arrow-right' }}"></i> {{ $t->acao() }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if ($os->observacao_conclusao)
        <div class="alert alert-success">
            <div class="fw-semibold"><i class="bi bi-check2-circle me-1"></i>Serviço executado</div>
            <div style="white-space: pre-line">{{ $os->observacao_conclusao }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('ocorrencias._detalhes')
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    @include('ocorrencias._galeria', ['fotos' => $ocorrencia->fotos->where('tipo', TipoFoto::Antes), 'titulo' => 'Antes'])
                </div>
                <div class="col-12 col-md-6">
                    @include('ocorrencias._galeria', ['fotos' => $ocorrencia->fotos->where('tipo', TipoFoto::Depois), 'titulo' => 'Depois'])
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i>Histórico da OS</div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        @foreach ($os->historico as $h)
                            <li>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    @if ($h->status_anterior)
                                        <x-status-badge :status="$h->status_anterior" /> <i class="bi bi-arrow-right small"></i>
                                    @endif
                                    <x-status-badge :status="$h->status_novo" />
                                </div>
                                <div class="small text-muted mt-1">{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->name ?? 'Sistema' }}</div>
                                @if ($h->comentario)<div class="small mt-1" style="white-space: pre-line">{{ $h->comentario }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if ($os->equipe)
                <div class="card mb-4">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-person-gear me-1"></i>Equipe {{ $os->equipe->nome }}</div>
                    <ul class="list-group list-group-flush small">
                        @forelse ($os->equipe->membros as $m)
                            <li class="list-group-item">{{ $m->nome }} <span class="text-muted">{{ $m->matricula }}</span></li>
                        @empty
                            <li class="list-group-item text-muted">Sem membros cadastrados.</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            @include('ocorrencias._analises')
        </div>
    </div>

    {{-- Modais de transição: somente as transições válidas --}}
    @can('transicionar', $os)
        @foreach ($transicoes as $t)
            <div class="modal fade" id="modal-{{ $t->value }}" tabindex="-1" aria-hidden="true" @if ($modalComErro === $t->value) data-abrir="1" @endif>
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('ordens-servico.transicionar', $os) }}" enctype="multipart/form-data" class="modal-content">
                        @csrf
                        <input type="hidden" name="status" value="{{ $t->value }}">
                        <div class="modal-header">
                            <h2 class="modal-title h5">{{ $t->acao() }} {{ $os->numero }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            @php $erros = $modalComErro === $t->value; @endphp

                            @if ($t === StatusOS::Programada)
                                <div class="mb-3">
                                    <label class="form-label" for="equipe_id_{{ $t->value }}">Equipe<span class="text-danger">*</span></label>
                                    <select name="equipe_id" id="equipe_id_{{ $t->value }}" class="form-select {{ $erros && $errors->has('equipe_id') ? 'is-invalid' : '' }}" required>
                                        <option value="">Selecione...</option>
                                        @foreach ($equipes as $e)
                                            <option value="{{ $e->id }}" @selected((string) old('equipe_id', $os->equipe_id) === (string) $e->id)>{{ $e->nome }} — {{ $e->especialidade }}</option>
                                        @endforeach
                                    </select>
                                    @if ($erros)@error('equipe_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="prazo_{{ $t->value }}">Prazo previsto</label>
                                    <input type="datetime-local" name="prazo_previsto" id="prazo_{{ $t->value }}" class="form-control"
                                           value="{{ old('prazo_previsto', $os->prazo_previsto?->format('Y-m-d\TH:i')) }}">
                                    <div class="form-text">Calculado pelo SLA; ajuste se necessário.</div>
                                </div>
                            @endif

                            @if ($t === StatusOS::Concluida)
                                <div class="mb-3">
                                    <label class="form-label" for="obs_{{ $t->value }}">Serviço executado<span class="text-danger">*</span></label>
                                    <textarea name="observacao_conclusao" id="obs_{{ $t->value }}" rows="4" required
                                              class="form-control {{ $erros && $errors->has('observacao_conclusao') ? 'is-invalid' : '' }}">{{ $erros ? old('observacao_conclusao') : '' }}</textarea>
                                    @if ($erros)@error('observacao_conclusao')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="fotos">Fotos de "depois"<span class="text-danger">*</span></label>
                                    <input type="file" name="fotos[]" id="fotos" accept="image/*" capture="environment" multiple class="d-none"
                                           data-max="{{ config('sistema.fotos.max_por_envio') }}" data-existentes="0" data-max-mb="10">
                                    <div><button type="button" class="btn btn-outline-primary" id="btn-camera"><i class="bi bi-camera"></i> Tirar / escolher foto</button></div>
                                    <div id="fotos-preview" class="d-flex flex-wrap gap-3 mt-2"></div>
                                    <div id="fotos-aviso" class="small text-danger mt-2"></div>
                                    @if ($erros)
                                        @error('fotos')<div class="text-danger small">{{ $message }}</div>@enderror
                                        @foreach ($errors->get('fotos.*') as $m)<div class="text-danger small">{{ $m[0] }}</div>@endforeach
                                    @endif
                                </div>
                            @endif

                            @if ($t !== StatusOS::Concluida)
                                <div class="mb-3">
                                    <label class="form-label" for="comentario_{{ $t->value }}">
                                        {{ $t === StatusOS::Cancelada ? 'Justificativa' : 'Comentário (opcional)' }}
                                        @if ($t === StatusOS::Cancelada)<span class="text-danger">*</span>@endif
                                    </label>
                                    <textarea name="comentario" id="comentario_{{ $t->value }}" rows="3" @if ($t === StatusOS::Cancelada) required @endif
                                              class="form-control {{ $erros && $errors->has('comentario') ? 'is-invalid' : '' }}">{{ $erros ? old('comentario') : '' }}</textarea>
                                    @if ($erros)@error('comentario')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-{{ str_replace('outline-', '', $cores[$t->value] ?? 'primary') }}">
                                <i class="bi bi-{{ $icones[$t->value] ?? 'check' }}"></i> Confirmar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('.modal[data-abrir="1"]');
    if (modal) new window.bootstrap.Modal(modal).show();
});
</script>
@endpush
