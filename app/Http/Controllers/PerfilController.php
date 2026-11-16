<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlterarSenhaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', ['user' => $request->user()]);
    }

    public function updateSenha(AlterarSenhaRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
