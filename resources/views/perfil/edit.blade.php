@extends('layouts.app')

@section('title', 'Meu perfil')

@section('content')
    <x-page-header titulo="Meu perfil" />

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar" style="width:56px;height:56px;font-size:1.2rem">{{ $user->iniciais() }}</span>
                        <div>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <x-status-badge :status="$user->role" />
                        </div>
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-4">Matrícula</dt><dd class="col-8">{{ $user->matricula }}</dd>
                        <dt class="col-4">E-mail</dt><dd class="col-8 text-break">{{ $user->email }}</dd>
                        <dt class="col-4">Desde</dt><dd class="col-8">{{ $user->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Alterar senha</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('perfil.senha') }}">
                        @csrf
                        @method('PUT')
                        <x-form.input name="senha_atual" type="password" label="Senha atual" autocomplete="current-password" required />
                        <x-form.input name="password" type="password" label="Nova senha" autocomplete="new-password" help="Mínimo de 8 caracteres." required />
                        <x-form.input name="password_confirmation" type="password" label="Confirme a nova senha" autocomplete="new-password" required />
                        <button class="btn btn-primary"><i class="bi bi-key"></i> Salvar nova senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
