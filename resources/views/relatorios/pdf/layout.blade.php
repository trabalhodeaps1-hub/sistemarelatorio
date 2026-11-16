<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $relatorio->titulo }}</title>
    <style>
        @page { margin: 105px 32px 48px 32px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9px; color: #1f2937; }
        header { position: fixed; top: -88px; left: 0; right: 0; height: 78px; border-bottom: 2px solid #0078DC; }
        header .marca { color: #0078DC; font-weight: bold; font-size: 10px; letter-spacing: .5px; }
        header h1 { font-size: 15px; margin: 2px 0 4px; color: #07132B; }
        header .meta { font-size: 8px; color: #4b5563; }
        header .meta span { margin-right: 10px; }
        header .gerado { position: absolute; right: 0; top: 0; text-align: right; font-size: 8px; color: #4b5563; }
        footer { position: fixed; bottom: -32px; left: 0; right: 0; height: 20px; border-top: 1px solid #d1d5db; font-size: 8px; color: #6b7280; padding-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #07132B; color: #fff; font-weight: bold; text-align: left; padding: 4px 5px; font-size: 8px; }
        td { padding: 4px 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #f6f7f9; }
        .num { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; background: #e5e7eb !important; }
        .resumo { margin-bottom: 10px; }
        .resumo td { border: 1px solid #d1d5db; background: #f3f4f7; width: 25%; }
        .resumo .rot { font-size: 7px; color: #6b7280; text-transform: uppercase; }
        .resumo .val { font-size: 12px; font-weight: bold; color: #07132B; }
        .vazio { padding: 30px; text-align: center; color: #6b7280; border: 1px dashed #d1d5db; }
        .atrasada { color: #b91c1c; font-weight: bold; }
        .ok { color: #047857; }
        h2 { font-size: 11px; color: #07132B; border-bottom: 1px solid #d1d5db; padding-bottom: 2px; margin: 14px 0 6px; }
    </style>
</head>
<body>
<header>
    <div class="marca">AUTORELATÓRIO INDUSTRIAL</div>
    <h1>{{ $relatorio->titulo }}</h1>
    <div class="meta">
        @foreach ($filtrosDescritos as $r => $v)<span><strong>{{ $r }}:</strong> {{ $v }}</span>@endforeach
    </div>
    <div class="gerado">Gerado por <strong>{{ $geradoPor }}</strong><br>em {{ $geradoEm }}</div>
</header>
<footer>{{ config('app.name') }} — documento gerado automaticamente</footer>

<main>
    @yield('conteudo')
</main>
</body>
</html>
