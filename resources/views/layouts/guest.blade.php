<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="auth-bg d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width: 420px">
        <div class="text-center text-white mb-4">
            <i class="bi bi-gear-wide-connected fs-1 text-warning"></i>
            <h1 class="h4 fw-bold mt-2 mb-0">AutoRelatório Industrial</h1>
            <p class="text-white-50 small mb-0">Ocorrências e ordens de serviço</p>
        </div>
        @yield('content')
    </div>
</div>
</body>
</html>
