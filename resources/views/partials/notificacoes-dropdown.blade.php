@php
    $naoLidas = $notificacoesNaoLidas ?? 0;
    $ultimas = $ultimasNotificacoes ?? collect();
    $icones = ['info' => 'info-circle text-info', 'alerta' => 'exclamation-triangle text-warning', 'critico' => 'exclamation-octagon text-danger'];
@endphp
<div class="dropdown">
    <button class="btn btn-link text-dark position-relative p-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notificações">
        <i class="bi bi-bell fs-5"></i>
        <span id="notif-contador" class="badge rounded-pill text-bg-danger notif-badge {{ $naoLidas ? '' : 'd-none' }}"
              data-url="{{ Route::has('notificacoes.contador') ? route('notificacoes.contador') : '' }}">{{ $naoLidas > 99 ? '99+' : $naoLidas }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 notif-dropdown">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong class="small">Notificações</strong>
            @if ($naoLidas && Route::has('notificacoes.ler-todas'))
                <form method="POST" action="{{ route('notificacoes.ler-todas') }}">
                    @csrf
                    <button class="btn btn-link btn-sm p-0 small" style="min-height:0">Marcar todas como lidas</button>
                </form>
            @endif
        </div>
        <div style="max-height: 360px; overflow-y: auto">
            @forelse ($ultimas as $n)
                <a href="{{ route('notificacoes.abrir', $n->id) }}" class="dropdown-item d-flex gap-2 py-2 border-bottom text-wrap {{ $n->read_at ? '' : 'bg-light' }}">
                    <i class="bi bi-{{ $icones[$n->data['nivel'] ?? 'info'] ?? $icones['info'] }} mt-1"></i>
                    <span class="small">
                        <span class="d-block fw-semibold">{{ $n->data['titulo'] ?? 'Notificação' }}</span>
                        <span class="d-block text-muted">{{ $n->data['mensagem'] ?? '' }}</span>
                        <span class="d-block text-muted" style="font-size:.7rem">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="text-center text-muted small py-4">Nenhuma notificação.</div>
            @endforelse
        </div>
        @if (Route::has('notificacoes.index'))
            <a href="{{ route('notificacoes.index') }}" class="d-block text-center small py-2">Ver todas</a>
        @endif
    </div>
</div>
