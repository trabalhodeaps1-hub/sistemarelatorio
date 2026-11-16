@extends('layouts.app')
@section('title', 'Auditoria')

@php $cores = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger', 'restored' => 'info']; @endphp

@section('content')
    <x-page-header titulo="Auditoria" subtitulo="Registro de criações, alterações e exclusões nos dados do sistema" />

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="de">De</label>
                <input type="date" name="de" id="de" class="form-control @error('de') is-invalid @enderror" value="{{ request('de') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="ate">Até</label>
                <input type="date" name="ate" id="ate" class="form-control @error('ate') is-invalid @enderror" value="{{ request('ate') }}">
                @error('ate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="user_id">Usuário</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $id => $nome)<option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $nome }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="acao">Ação</label>
                <select name="acao" id="acao" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($acoes as $v => $r)<option value="{{ $v }}" @selected(request('acao') === $v)>{{ $r }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="modelo">Registro</label>
                <select name="modelo" id="modelo" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($modelos as $v => $r)<option value="{{ $v }}" @selected(request('modelo') === $v)>{{ $r }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="card">
        @if ($logs->isEmpty())
            <x-empty-state icone="shield-check" titulo="Nenhum registro de auditoria" />
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 table-cards">
                    <thead class="table-light"><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Registro</th><th>IP</th><th>Detalhes</th></tr></thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td data-label="Data" class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td data-label="Usuário">{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td data-label="Ação"><span class="badge text-bg-{{ $cores[$log->acao] ?? 'secondary' }}">{{ $acoes[$log->acao] ?? $log->acao }}</span></td>
                            <td data-label="Registro" class="text-nowrap">{{ $log->modelo }} #{{ $log->model_id }}</td>
                            <td data-label="IP" class="small text-muted">{{ $log->ip ?? '—' }}</td>
                            <td data-label="Detalhes" style="max-width: 420px">
                                <details>
                                    <summary class="small">ver</summary>
                                    @if ($log->acao === 'updated')
                                        <table class="table table-sm small mb-0 mt-1">
                                            <thead><tr><th>Campo</th><th>Antes</th><th>Depois</th></tr></thead>
                                            @foreach ($log->dados['depois'] ?? [] as $campo => $novo)
                                                <tr>
                                                    <td class="fw-semibold">{{ $campo }}</td>
                                                    <td class="text-danger text-break">{{ is_scalar($log->dados['antes'][$campo] ?? null) ? $log->dados['antes'][$campo] : json_encode($log->dados['antes'][$campo] ?? null, JSON_UNESCAPED_UNICODE) }}</td>
                                                    <td class="text-success text-break">{{ is_scalar($novo) ? $novo : json_encode($novo, JSON_UNESCAPED_UNICODE) }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    @else
                                        <pre class="small bg-light p-2 rounded mb-0 mt-1" style="white-space: pre-wrap">{{ json_encode($log->dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                </details>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
