<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="app-wrapper">
    {{-- Sidebar: fixa no desktop, offcanvas no mobile --}}
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="d-flex align-items-center justify-content-between px-3 py-3">
            <a href="{{ route('dashboard') }}" class="brand fs-5" id="sidebarLabel">
                <i class="bi bi-gear-wide-connected me-1"></i> AutoRelatório
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Fechar"></button>
        </div>
        <nav class="px-2 pb-4">
            @include('partials.menu')
        </nav>
    </aside>

    <div class="main-content d-flex flex-column min-vh-100">
        <header class="topbar d-flex align-items-center px-3 gap-2">
            <button class="btn btn-link text-dark d-lg-none p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menu">
                <i class="bi bi-list fs-3"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-2">
                @include('partials.notificacoes-dropdown')

                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none text-dark d-flex align-items-center gap-2 p-1" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ auth()->user()->iniciais() }}</span>
                        <span class="d-none d-md-inline text-start lh-sm">
                            <span class="d-block fw-semibold small">{{ auth()->user()->name }}</span>
                            <span class="d-block text-muted" style="font-size:.75rem">{{ auth()->user()->role->label() }}</span>
                        </span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="bi bi-person me-2"></i>Meu perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="flex-grow-1 p-3 p-lg-4">
            <x-flash />
            @yield('content')
        </main>

        <footer class="text-center text-muted small py-3">
            {{ config('app.name') }} &copy; {{ date('Y') }}
        </footer>
    </div>
</div>

{{-- Modal da galeria de fotos --}}
<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center pt-0">
                <img src="" alt="Foto" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
