@props(['titulo', 'valor', 'icone' => 'bar-chart', 'cor' => 'primary', 'href' => null, 'legenda' => null])
<div {{ $attributes->merge(['class' => 'card stat-card h-100']) }}>
    <div class="card-body d-flex align-items-center gap-3">
        <span class="stat-icon bg-{{ $cor }}-subtle text-{{ $cor }}"><i class="bi bi-{{ $icone }}"></i></span>
        <div class="flex-grow-1">
            <div class="text-muted small">{{ $titulo }}</div>
            <div class="stat-value">{{ $valor }}</div>
            @if ($legenda)<div class="text-muted" style="font-size:.75rem">{{ $legenda }}</div>@endif
        </div>
    </div>
    @if ($href)
        <a href="{{ $href }}" class="stretched-link" aria-label="{{ $titulo }}"></a>
    @endif
</div>
