<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name') }}</title>
    @php
        $temVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
    @endphp
    @if ($temVite)
        @vite(['resources/scss/app.scss'])
    @endif
</head>
<body>
<div class="auth-bg d-flex align-items-center justify-content-center p-3" style="min-height:100vh;background:#07132B">
    <div class="card shadow text-center" style="max-width: 460px; width: 100%">
        <div class="card-body p-5">
            <i class="bi bi-@yield('icon', 'exclamation-triangle') display-4 text-warning"></i>
            <div class="display-5 fw-bold mt-2">@yield('code')</div>
            <h1 class="h5 fw-semibold mt-2">@yield('title')</h1>
            <p class="text-muted">@yield('message')</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> Início</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
