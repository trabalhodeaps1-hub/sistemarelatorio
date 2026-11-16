<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StatusOS;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Ocorrencia;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        // Indicadores do painel de boas-vindas (cache de 10 min para não pesar no acesso)
        $indicadores = Cache::remember('login.indicadores', now()->addMinutes(10), function () {
            $concluidas = OrdemServico::where('status', StatusOS::Concluida->value);
            $total = (clone $concluidas)->count();
            $noPrazo = (clone $concluidas)->whereColumn('concluida_em', '<=', 'prazo_previsto')->count();

            return [
                'inspecoes' => Ocorrencia::count(),
                'no_prazo' => $total ? (int) round($noPrazo / $total * 100) : null,
            ];
        });

        return view('auth.login', ['indicadores' => $indicadores]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $login = trim($request->string('login'));
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'matricula';

        $user = User::where($campo, $campo === 'email' ? mb_strtolower($login) : $login)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['login' => 'Credenciais inválidas.']);
        }

        if (! $user->ativo) {
            throw ValidationException::withMessages(['login' => 'Seu usuário está inativo. Procure o administrador.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
