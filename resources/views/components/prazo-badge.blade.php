@props(['os'])
@if ($os->status === \App\Enums\StatusOS::Concluida)
    @if ($os->atrasada())
        <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle">Concluída com atraso</span>
    @else
        <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">No prazo</span>
    @endif
@elseif (! $os->status->finalizada())
    @if ($os->atrasada())
        <span class="badge text-bg-danger"><i class="bi bi-alarm"></i> Atrasada</span>
    @elseif ($os->venceEm24h())
        <span class="badge text-bg-warning"><i class="bi bi-hourglass-split"></i> Vence em 24h</span>
    @endif
@endif
