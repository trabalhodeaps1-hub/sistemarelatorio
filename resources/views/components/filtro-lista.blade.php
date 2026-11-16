@props(['placeholder' => 'Buscar...'])
{{-- Filtro padrão das listagens de cadastro: busca + ativo/inativo --}}
<form method="GET" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md">
            <label class="form-label small mb-1" for="busca">Busca</label>
            <input type="search" name="busca" id="busca" class="form-control" value="{{ request('busca') }}" placeholder="{{ $placeholder }}">
        </div>
        {{ $slot }}
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="situacao">Situação</label>
            <select name="situacao" id="situacao" class="form-select">
                <option value="">Todos</option>
                <option value="ativos" @selected(request('situacao') === 'ativos')>Ativos</option>
                <option value="inativos" @selected(request('situacao') === 'inativos')>Inativos</option>
            </select>
        </div>
        <div class="col-6 col-md-auto d-flex gap-2">
            <button class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> Filtrar</button>
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary" title="Limpar"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>
