<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso: ->middleware('role:supervisor,gerente').
 * O master tem acesso a todos os grupos.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role === Role::Master || $user->hasRole(...$roles)) {
            return $next($request);
        }

        abort(403, 'Você não tem permissão para acessar esta área.');
    }
}
