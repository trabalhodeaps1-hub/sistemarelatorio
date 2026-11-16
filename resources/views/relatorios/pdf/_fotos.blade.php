@if ($fotos->isEmpty())
    <div class="vazio" style="padding:10px">Sem fotos.</div>
@else
    <table>
        @foreach ($fotos->chunk(3) as $linha)
            <tr>
                @foreach ($linha as $src)
                    <td style="width:33%;text-align:center;border:0;background:none">
                        <img src="{{ $src }}" style="max-width:100%;max-height:170px">
                    </td>
                @endforeach
                @for ($i = $linha->count(); $i < 3; $i++)<td style="border:0;background:none"></td>@endfor
            </tr>
        @endforeach
    </table>
@endif
