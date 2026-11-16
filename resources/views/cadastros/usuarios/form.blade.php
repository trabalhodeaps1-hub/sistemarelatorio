@extends('layouts.app')
@section('title', $usuario->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
    <x-page-header :titulo="$usuario->exists ? 'Editar usuário' : 'Novo usuário'">
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </x-page-header>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="card">
                @csrf
                @if ($usuario->exists) @method('PUT') @endif
                <div class="card-body">
                    <x-form.input name="name" label="Nome" :value="$usuario->name" required />
                    <div class="row">
                        <div class="col-md-5"><x-form.input name="matricula" label="Matrícula" :value="$usuario->matricula" required /></div>
                        <div class="col-md-7"><x-form.input name="email" type="email" label="E-mail" :value="$usuario->email" required /></div>
                    </div>
                    <x-form.select name="role" label="Perfil" :options="$roles" :value="$usuario->role" required />

                    @unless ($usuario->exists)
                        <div class="row">
                            <div class="col-md-6"><x-form.input name="password" type="password" label="Senha" autocomplete="new-password" required help="Mínimo de 8 caracteres." /></div>
                            <div class="col-md-6"><x-form.input name="password_confirmation" type="password" label="Confirme a senha" autocomplete="new-password" required /></div>
                        </div>
                    @endunless

                    <x-form.checkbox name="ativo" label="Usuário ativo" :checked="$usuario->ativo" />
                </div>
                <div class="card-footer bg-white text-end">
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                </div>
            </form>
        </div>

        @if ($usuario->exists)
            <div class="col-12 col-lg-5">
                <form method="POST" action="{{ route('usuarios.senha', $usuario) }}" class="card">
                    @csrf @method('PUT')
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-key me-1"></i> Redefinir senha</div>
                    <div class="card-body">
                        <x-form.input name="nova_senha" type="password" label="Nova senha" autocomplete="new-password" required />
                        <x-form.input name="nova_senha_confirmation" type="password" label="Confirme a nova senha" autocomplete="new-password" required />
                        <button class="btn btn-warning w-100"><i class="bi bi-arrow-repeat"></i> Redefinir senha</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
