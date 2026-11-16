{{-- Tabela genérica de relatório (HTML). Variável: $relatorio --}}
@if (empty($relatorio->linhas))
    <x-empty-state icone="clipboard-x" titulo="Nenhum registro no período" mensagem="Ajuste os filtros e tente novamente." />
@else
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover align-middle mb-0 table-cards">
            <thead class="table-light">
            <tr>
                @foreach ($relatorio->colunas as $chave => $rotulo)
                    <th class="{{ in_array($chave, $relatorio->numericas) ? 'text-end' : '' }} text-nowrap">{{ $rotulo }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($relatorio->linhas as $linha)
                <tr class="{{ ($linha['criticidade'] ?? null) === 'Total' ? 'fw-bold table-secondary' : '' }}">
                    @foreach ($relatorio->colunas as $chave => $rotulo)
                        <td data-label="{{ $rotulo }}" class="{{ in_array($chave, $relatorio->numericas) ? 'text-end' : '' }}">
                            @if ($chave === 'situacao')
                                <span class="badge {{ $linha[$chave] === 'Atrasada' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $linha[$chave] }}</span>
                            @else
                                {{ $linha[$chave] }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
