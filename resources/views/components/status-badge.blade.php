@props(['status', 'icon' => null])
@if ($status)
    <span {{ $attributes->merge(['class' => 'badge '.$status->badgeClass()]) }}>
        @if ($icon)<i class="bi bi-{{ $icon }} me-1"></i>@endif{{ $status->label() }}
    </span>
@endif
