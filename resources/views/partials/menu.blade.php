@php
    use App\Enums\Role;
    $u = auth()->user();
    $is = fn (Role ...$roles) => $u->role === Role::Master || in_array($u->role, $roles, true);

    $item = function (string $rota, string $icone, string $rotulo, ?string $ativo = null) {
        if (! Route::has($rota)) {
            return '';
        }
        $classe = request()->routeIs($ativo ?? $rota) ? ' active' : '';

        return '<li class="nav-item"><a class="nav-link'.$classe.'" href="'.route($rota).'"><i class="bi bi-'.$icone.'"></i><span>'.e($rotulo).'</span></a></li>';
    };
@endphp

<ul class="nav flex-column gap-1">
    {!! $item('dashboard', 'speedometer2', 'Dashboard') !!}

    @if ($is(Role::Tecnico))
        <li class="nav-section">Ocorrências</li>
        {!! $item('ocorrencias.create', 'exclamation-triangle', 'Apontar Problema') !!}
        {!! $item('ocorrencias.index', 'journal-text', 'Minhas ocorrências', 'ocorrencias.index') !!}
    @endif

    @if ($is(Role::Supervisor))
        <li class="nav-section">Análise</li>
        {!! $item('analises.index', 'clipboard-check', 'Fila de análise', 'analises.*') !!}
    @endif

    @if ($is(Role::Gerente))
        <li class="nav-section">Manutenção</li>
        {!! $item('ordens-servico.index', 'tools', 'Ordens de serviço', 'ordens-servico.*') !!}
    @endif

    @if ($is(Role::Supervisor, Role::Gerente))
        <li class="nav-section">Relatórios</li>
        {!! $item('relatorios.index', 'file-earmark-bar-graph', 'Relatórios', 'relatorios.*') !!}
    @endif

    @if ($u->isMaster())
        <li class="nav-section">Cadastros</li>
        {!! $item('usuarios.index', 'people', 'Usuários', 'usuarios.*') !!}
        {!! $item('areas.index', 'diagram-3', 'Áreas', 'areas.*') !!}
        {!! $item('equipamentos.index', 'cpu', 'Equipamentos', 'equipamentos.*') !!}
        {!! $item('categorias.index', 'tags', 'Categorias', 'categorias.*') !!}
        {!! $item('motivos.index', 'x-octagon', 'Motivos de reprovação', 'motivos.*') !!}
        {!! $item('equipes.index', 'person-gear', 'Equipes', 'equipes.*') !!}
        {!! $item('sla.index', 'stopwatch', 'SLA', 'sla.*') !!}
        <li class="nav-section">Sistema</li>
        {!! $item('logs.index', 'shield-check', 'Auditoria', 'logs.*') !!}
    @endif

    <li class="nav-section">Conta</li>
    {!! $item('notificacoes.index', 'bell', 'Notificações', 'notificacoes.*') !!}
    {!! $item('perfil.edit', 'person-circle', 'Meu perfil', 'perfil.*') !!}
</ul>
