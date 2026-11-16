@props(['titulo', 'subtitulo' => null])
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1>{{ $titulo }}</h1>
        @if ($subtitulo)<div class="text-muted small">{{ $subtitulo }}</div>@endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
