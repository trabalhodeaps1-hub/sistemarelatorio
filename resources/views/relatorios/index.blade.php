@extends('layouts.app')
@section('title', 'Relatórios')

@section('content')
    <x-page-header titulo="Relatórios" subtitulo="Defina os filtros e escolha o relatório" />

    @include('relatorios._filtros', ['acao' => route('relatorios.show', array_key_first($tipos))])

    <div class="row g-3">
        @foreach ($tipos as $chave => [$titulo, $descricao, $icone])
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex gap-3">
                        <span class="stat-icon bg-primary-subtle text-primary flex-shrink-0" style="width:48px;height:48px;border-radius:.75rem;display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem">
                            <i class="bi bi-{{ $icone }}"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $titulo }}</div>
                            <div class="small text-muted mb-3">{{ $descricao }}</div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" form="form-relatorio" formaction="{{ route('relatorios.show', $chave) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Prévia
                                </button>
                                <button type="submit" form="form-relatorio" formaction="{{ route('relatorios.pdf', $chave) }}" formtarget="_blank" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 bg-light">
                <div class="card-body">
                    <div class="fw-semibold"><i class="bi bi-file-earmark-text me-1"></i>Ficha individual da OS</div>
                    <div class="small text-muted">Disponível no detalhe de cada OS (botão "Ficha PDF"), com fotos de antes e depois e histórico.</div>
                </div>
            </div>
        </div>
    </div>
@endsection
