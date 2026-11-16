{{-- Filtros comuns. Variáveis: $areas, $equipes, $statuses, $criticidades, $acao (URL) --}}
@php
    $inicio = request('data_inicio', now()->startOfMonth()->format('Y-m-d'));
    $fim = request('data_fim', now()->format('Y-m-d'));
@endphp
<form method="GET" action="{{ $acao }}" id="form-relatorio" class="card card-body mb-3" data-no-lock>
    <div class="row g-2">
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label small mb-1" for="data_inicio">De<span class="text-danger">*</span></label>
            <input type="date" name="data_inicio" id="data_inicio" value="{{ $inicio }}" required class="form-control @error('data_inicio') is-invalid @enderror">
            @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label small mb-1" for="data_fim">Até<span class="text-danger">*</span></label>
            <input type="date" name="data_fim" id="data_fim" value="{{ $fim }}" required class="form-control @error('data_fim') is-invalid @enderror">
            @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label small mb-1" for="area_id">Área</label>
            <select name="area_id" id="area_id" class="form-select" data-equipamentos-url="{{ url('/areas/__AREA__/equipamentos') }}">
                <option value="">Todas</option>
                @foreach ($areas as $id => $nome)
                    <option value="{{ $id }}" @selected((string) request('area_id') === (string) $id)>{{ $nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label small mb-1" for="equipamento_id">Equipamento</label>
            <select name="equipamento_id" id="equipamento_id" class="form-select" data-sem-estrutura="1" data-selecionado="{{ request('equipamento_id') }}">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="col-6 col-md-2 col-xl-2">
            <label class="form-label small mb-1" for="status">Status da OS</label>
            <select name="status" id="status" class="form-select">
                <option value="">Todos</option>
                @foreach ($statuses as $v => $r)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $r }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 col-xl-2">
            <label class="form-label small mb-1" for="criticidade">Criticidade</label>
            <select name="criticidade" id="criticidade" class="form-select">
                <option value="">Todas</option>
                @foreach ($criticidades as $v => $r)<option value="{{ $v }}" @selected(request('criticidade') === $v)>{{ $r }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 col-xl-2">
            <label class="form-label small mb-1" for="equipe_id">Equipe</label>
            <select name="equipe_id" id="equipe_id" class="form-select">
                <option value="">Todas</option>
                @foreach ($equipes as $v => $r)<option value="{{ $v }}" @selected((string) request('equipe_id') === (string) $v)>{{ $r }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-xl d-flex align-items-end gap-2 flex-wrap">
            {{ $slot ?? '' }}
            @isset($botoes){!! $botoes !!}@endisset
        </div>
    </div>
    <div class="form-text">Período obrigatório, máximo de {{ config('sistema.relatorios.max_meses') }} meses.</div>
</form>
