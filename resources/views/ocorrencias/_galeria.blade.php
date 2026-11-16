{{-- Galeria de fotos com modal. Variáveis: $fotos, $titulo --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-images me-1"></i>{{ $titulo ?? 'Fotos' }} <span class="text-muted">({{ $fotos->count() }})</span></div>
    <div class="card-body">
        @if ($fotos->isEmpty())
            <div class="text-muted small">Nenhuma foto.</div>
        @else
            <div class="d-flex flex-wrap gap-2">
                @foreach ($fotos as $foto)
                    <img src="{{ $foto->url() }}" alt="Foto {{ $loop->iteration }}" class="foto-thumb" loading="lazy"
                         data-bs-toggle="modal" data-bs-target="#modalFoto" data-foto="{{ $foto->url() }}">
                @endforeach
            </div>
        @endif
    </div>
</div>
