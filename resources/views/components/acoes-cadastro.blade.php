@props(['rota', 'model', 'rotulo' => 'registro'])
<div class="d-flex gap-1 justify-content-end flex-wrap">
    <a href="{{ route($rota.'.edit', $model) }}" class="btn btn-sm btn-outline-primary" title="Editar">
        <i class="bi bi-pencil"></i><span class="d-md-none">Editar</span>
    </a>
    <form method="POST" action="{{ route($rota.'.alternar', $model) }}" class="d-inline">
        @csrf @method('PATCH')
        <button class="btn btn-sm {{ $model->ativo ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $model->ativo ? 'Desativar' : 'Ativar' }}">
            <i class="bi bi-{{ $model->ativo ? 'pause-circle' : 'play-circle' }}"></i><span class="d-md-none">{{ $model->ativo ? 'Desativar' : 'Ativar' }}</span>
        </button>
    </form>
    <form method="POST" action="{{ route($rota.'.destroy', $model) }}" class="d-inline" data-confirm="Excluir este {{ $rotulo }}? Registros com vínculos só podem ser desativados.">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" title="Excluir">
            <i class="bi bi-trash"></i><span class="d-md-none">Excluir</span>
        </button>
    </form>
</div>
