<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="login-page">
<div class="login-wrapper">
    {{-- Painel de boas-vindas --}}
    <section class="login-hero">
        <div class="login-brand">
            <span class="login-brand-icon"><i class="bi bi-diamond-fill"></i></span>
            <span class="lh-1">
                <span class="login-brand-nome">AutoRelatório</span>
                <span class="login-brand-sub">Industrial</span>
            </span>
        </div>
        <div class="login-brand-empresa">{{ config('sistema.empresa') }}</div>

        <h1 class="login-titulo">
            Inspeções<br>
            <span class="text-primary-light">sem papel.</span><br>
            Relatórios<br>
            automáticos.
        </h1>
        <p class="login-texto">
            Registre problemas em campo, documente com fotos e gere relatórios técnicos
            automaticamente — de qualquer dispositivo.
        </p>

        <div class="login-indicadores">
            <div>
                <div class="valor">{{ number_format($indicadores['inspecoes'], 0, ',', '.') }}</div>
                <div class="rotulo">Inspeções realizadas</div>
            </div>
            <div>
                <div class="valor">{{ $indicadores['no_prazo'] !== null ? $indicadores['no_prazo'].'%' : '—' }}</div>
                <div class="rotulo">Relatórios no prazo</div>
            </div>
            <div>
                <div class="valor">{{ config('sistema.plantas') }} {{ config('sistema.plantas') == 1 ? 'planta' : 'plantas' }}</div>
                <div class="rotulo">Unidades conectadas</div>
            </div>
        </div>
    </section>

    {{-- Formulário --}}
    <section class="login-form-area">
        <div class="login-form-box">
            <div class="login-brand login-brand-mobile d-lg-none mb-4">
                <span class="login-brand-icon"><i class="bi bi-diamond-fill"></i></span>
                <span class="lh-1">
                    <span class="login-brand-nome text-dark">AutoRelatório</span>
                    <span class="login-brand-sub">Industrial</span>
                </span>
            </div>

            <h2 class="login-form-titulo">Acesso ao sistema</h2>
            <p class="login-form-sub">Use suas credenciais corporativas</p>

            @if ($errors->any())
                <div class="login-alerta" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label fw-semibold small">Matrícula ou E-mail</label>
                    <input type="text" name="login" id="login" value="{{ old('login') }}" class="form-control login-input"
                           autocomplete="username" autofocus required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small">Senha</label>
                    <input type="password" name="password" id="password" class="form-control login-input"
                           autocomplete="current-password" required>
                </div>
                <div class="form-check mb-3 small">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label text-secondary" for="remember">Manter conectado</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">Entrar</button>
            </form>

            <p class="text-center small mt-3 mb-0 text-secondary">
                Não tenho uma conta.
                <a href="mailto:{{ config('sistema.suporte_email') }}?subject={{ rawurlencode('Solicitação de acesso - '.config('app.name')) }}" class="fw-semibold text-decoration-none">Solicitar acesso</a>
            </p>

            <div class="login-suporte">
                Dificuldades de acesso? Contate o suporte de TI<br>
                <a href="mailto:{{ config('sistema.suporte_email') }}">{{ config('sistema.suporte_email') }}</a>
            </div>

            <div class="login-rodape">
                v{{ config('sistema.versao') }} · Sistema interno {{ config('sistema.empresa') }} · © {{ date('Y') }}
            </div>
        </div>
    </section>
</div>
</body>
</html>
