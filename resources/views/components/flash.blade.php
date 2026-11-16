@php
    $tipos = [
        'success' => ['success', 'check-circle'],
        'error' => ['danger', 'x-circle'],
        'warning' => ['warning', 'exclamation-triangle'],
        'info' => ['info', 'info-circle'],
    ];
@endphp
@foreach ($tipos as $chave => [$cor, $icone])
    @if (session($chave))
        <div class="alert alert-{{ $cor }} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-{{ $icone }}"></i>
            <div>{{ session($chave) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
@endforeach
@php $gerais = collect(['excluir', 'geral', 'transicao'])->filter(fn ($k) => $errors->has($k)); @endphp
@foreach ($gerais as $k)
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-octagon"></i>
        <div>{{ $errors->first($k) }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endforeach
@if ($errors->any() && $gerais->isEmpty())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-octagon me-1"></i> Verifique os campos destacados abaixo.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
