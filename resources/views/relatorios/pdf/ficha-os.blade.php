@extends('relatorios.pdf.layout')

@section('conteudo')
    <table class="resumo">
        <tr>
            <td><div class="rot">Status</div><div class="val">{{ $os->status->label() }}</div></td>
            <td><div class="rot">Criticidade</div><div class="val">{{ $os->criticidade->label() }}</div></td>
            <td><div class="rot">Prazo previsto</div><div class="val">{{ $os->prazo_previsto?->format('d/m/Y H:i') ?? '—' }}</div></td>
            <td><div class="rot">Concluída em</div><div class="val {{ $os->atrasada() ? 'atrasada' : '' }}">{{ $os->concluida_em?->format('d/m/Y H:i') ?? '—' }}</div></td>
        </tr>
    </table>

    <h2>Ocorrência {{ $ocorrencia->numero }}</h2>
    <table>
        <tr><td style="width:22%"><strong>Título</strong></td><td>{{ $ocorrencia->titulo }}</td></tr>
        <tr><td><strong>Área</strong></td><td>{{ $ocorrencia->area->nome_completo }}</td></tr>
        <tr><td><strong>Equipamento</strong></td><td>{{ $ocorrencia->equipamento?->nome_completo ?? 'Estrutura / Não se aplica' }}</td></tr>
        <tr><td><strong>Categoria</strong></td><td>{{ $ocorrencia->categoria->nome }}</td></tr>
        <tr><td><strong>Local</strong></td><td>{{ $ocorrencia->local ?: '—' }}</td></tr>
        <tr><td><strong>Registrada por</strong></td><td>{{ $ocorrencia->user->name }} ({{ $ocorrencia->user->matricula }}) em {{ $ocorrencia->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td><strong>Condições</strong></td><td>
            {{ $ocorrencia->risco_pessoas ? 'Risco a pessoas. ' : '' }}{{ $ocorrencia->equipamento_parado ? 'Equipamento parado.' : '' }}
            {{ ! $ocorrencia->risco_pessoas && ! $ocorrencia->equipamento_parado ? '—' : '' }}
        </td></tr>
        <tr><td><strong>Descrição</strong></td><td>{!! nl2br(e($ocorrencia->descricao)) !!}</td></tr>
    </table>

    <h2>Execução</h2>
    <table>
        <tr><td style="width:22%"><strong>Equipe</strong></td><td>{{ $os->equipe?->nome ?? '—' }}
            @if ($os->equipe && $os->equipe->membros->isNotEmpty())
                <span style="color:#6b7280">({{ $os->equipe->membros->pluck('nome')->implode(', ') }})</span>
            @endif
        </td></tr>
        <tr><td><strong>Aberta em</strong></td><td>{{ $os->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td><strong>Iniciada em</strong></td><td>{{ $os->iniciada_em?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td><strong>Serviço executado</strong></td><td>{!! $os->observacao_conclusao ? nl2br(e($os->observacao_conclusao)) : '—' !!}</td></tr>
    </table>

    <h2>Fotos — antes</h2>
    @include('relatorios.pdf._fotos', ['fotos' => $fotosAntes])

    <h2>Fotos — depois</h2>
    @include('relatorios.pdf._fotos', ['fotos' => $fotosDepois])

    <h2>Histórico</h2>
    <table>
        <thead><tr><th style="width:18%">Data</th><th style="width:28%">Transição</th><th style="width:18%">Usuário</th><th>Comentário</th></tr></thead>
        <tbody>
        @foreach ($ocorrencia->analises->sortBy('created_at') as $a)
            <tr>
                <td>{{ $a->created_at->format('d/m/Y H:i') }}</td>
                <td>Ocorrência {{ mb_strtolower($a->acao->label()) }}</td>
                <td>{{ $a->user->name }}</td>
                <td>{{ $a->motivo ? $a->motivo->descricao.'. ' : '' }}{{ $a->comentario }}</td>
            </tr>
        @endforeach
        @foreach ($os->historico as $h)
            <tr>
                <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $h->status_anterior ? $h->status_anterior->label().' → ' : 'OS ' }}{{ $h->status_novo->label() }}</td>
                <td>{{ $h->user?->name ?? 'Sistema' }}</td>
                <td>{{ $h->comentario }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
