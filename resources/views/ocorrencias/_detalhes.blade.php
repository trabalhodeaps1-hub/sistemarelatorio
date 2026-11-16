{{-- Dados completos da ocorrência. Variável: $ocorrencia --}}
<div class="card mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-bold">{{ $ocorrencia->numero }}</span>
            <span class="text-muted small ms-1">registrada {{ $ocorrencia->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <x-status-badge :status="$ocorrencia->status" />
            <x-status-badge :status="$ocorrencia->criticidade" icon="speedometer" />
        </div>
    </div>
    <div class="card-body">
        <h2 class="h5 fw-bold">{{ $ocorrencia->titulo }}</h2>

        @if ($ocorrencia->risco_pessoas || $ocorrencia->equipamento_parado)
            <div class="d-flex flex-wrap gap-2 mb-3">
                @if ($ocorrencia->risco_pessoas)
                    <span class="badge text-bg-danger"><i class="bi bi-person-exclamation me-1"></i>Risco a pessoas</span>
                @endif
                @if ($ocorrencia->equipamento_parado)
                    <span class="badge text-bg-warning"><i class="bi bi-stop-circle me-1"></i>Equipamento parado</span>
                @endif
            </div>
        @endif

        <p style="white-space: pre-line">{{ $ocorrencia->descricao }}</p>

        <dl class="row small mb-0">
            <dt class="col-sm-4 col-lg-3">Área</dt>
            <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->area->nome_completo }}</dd>

            <dt class="col-sm-4 col-lg-3">Equipamento</dt>
            <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->equipamento?->nome_completo ?? 'Estrutura / Não se aplica' }}</dd>

            <dt class="col-sm-4 col-lg-3">Categoria</dt>
            <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->categoria->nome }}</dd>

            <dt class="col-sm-4 col-lg-3">Local</dt>
            <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->local ?: '—' }}</dd>

            <dt class="col-sm-4 col-lg-3">Criticidade sugerida</dt>
            <dd class="col-sm-8 col-lg-9">
                {{ $ocorrencia->criticidade_sugerida->label() }}
                @if ($ocorrencia->criticidade !== $ocorrencia->criticidade_sugerida)
                    <span class="text-muted">→ ajustada para <strong>{{ $ocorrencia->criticidade->label() }}</strong></span>
                @endif
            </dd>

            <dt class="col-sm-4 col-lg-3">Registrada por</dt>
            <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->user->name }} ({{ $ocorrencia->user->matricula }})</dd>

            @if ($ocorrencia->reenvios)
                <dt class="col-sm-4 col-lg-3">Reenvios</dt>
                <dd class="col-sm-8 col-lg-9">{{ $ocorrencia->reenvios }}</dd>
            @endif

            @if ($ocorrencia->observacoes)
                <dt class="col-sm-4 col-lg-3">Observações</dt>
                <dd class="col-sm-8 col-lg-9" style="white-space: pre-line">{{ $ocorrencia->observacoes }}</dd>
            @endif
        </dl>
    </div>
</div>
