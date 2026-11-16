@extends('relatorios.pdf.layout')

@section('conteudo')
    @if ($relatorio->resumo)
        <table class="resumo">
            @foreach (array_chunk($relatorio->resumo, 4, true) as $grupo)
                <tr>
                    @foreach ($grupo as $rotulo => $valor)
                        <td><div class="rot">{{ $rotulo }}</div><div class="val">{{ $valor }}</div></td>
                    @endforeach
                    @for ($i = count($grupo); $i < 4; $i++)<td style="border:0;background:none"></td>@endfor
                </tr>
            @endforeach
        </table>
    @endif

    @if (empty($relatorio->linhas))
        <div class="vazio">Nenhum registro encontrado para os filtros informados.</div>
    @else
        <table>
            <thead>
            <tr>
                @foreach ($relatorio->colunas as $chave => $rotulo)
                    <th class="{{ in_array($chave, $relatorio->numericas) ? 'num' : '' }}">{{ $rotulo }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($relatorio->linhas as $linha)
                <tr class="{{ ($linha['criticidade'] ?? null) === 'Total' ? 'total' : '' }}">
                    @foreach ($relatorio->colunas as $chave => $rotulo)
                        <td class="{{ in_array($chave, $relatorio->numericas) ? 'num' : '' }} {{ $chave === 'situacao' ? ($linha[$chave] === 'Atrasada' ? 'atrasada' : 'ok') : '' }}">{{ $linha[$chave] }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <p style="color:#6b7280;margin-top:6px">{{ count($relatorio->linhas) }} registro(s).</p>
    @endif
@endsection
