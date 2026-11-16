@props(['icone' => 'inbox', 'titulo' => 'Nada por aqui', 'mensagem' => null])
<div {{ $attributes->merge(['class' => 'text-center text-muted py-5']) }}>
    <i class="bi bi-{{ $icone }} display-5 d-block mb-2 opacity-50"></i>
    <div class="fw-semibold">{{ $titulo }}</div>
    @if ($mensagem)<div class="small">{{ $mensagem }}</div>@endif
    @if ($slot->isNotEmpty())<div class="mt-3">{{ $slot }}</div>@endif
</div>
