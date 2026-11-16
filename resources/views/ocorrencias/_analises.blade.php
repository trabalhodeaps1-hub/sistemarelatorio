{{-- Histórico de análises. Variável: $ocorrencia (com analises carregadas) --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard-check me-1"></i>Histórico de análises</div>
    <div class="card-body">
        @if ($ocorrencia->analises->isEmpty())
            <div class="text-muted small">Aguardando análise do supervisor.</div>
        @else
            <ul class="timeline mb-0">
                @foreach ($ocorrencia->analises as $a)
                    <li>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <x-status-badge :status="$a->acao" />
                            <span class="small text-muted">{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->user->name }}</span>
                        </div>
                        @if ($a->motivo)
                            <div class="small mt-1"><strong>Motivo:</strong> {{ $a->motivo->descricao }}</div>
                        @endif
                        @if ($a->comentario)
                            <div class="small mt-1" style="white-space: pre-line">{{ $a->comentario }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
