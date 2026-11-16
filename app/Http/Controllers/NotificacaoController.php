<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacaoController extends Controller
{
    public function index(Request $request): View
    {
        $notificacoes = $request->user()->notifications()
            ->when($request->input('filtro') === 'nao-lidas', fn ($q) => $q->whereNull('read_at'))
            ->paginate(20)
            ->withQueryString();

        return view('notificacoes.index', compact('notificacoes'));
    }

    /** Marca como lida e segue para a URL da notificação. */
    public function abrir(Request $request, string $id): RedirectResponse
    {
        $notificacao = $request->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();

        $url = $notificacao->data['url'] ?? null;

        // Só segue URLs internas (relativas)
        return $url && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : redirect()->route('notificacoes.index');
    }

    public function ler(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function lerTodas(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }

    public function contador(Request $request): JsonResponse
    {
        return response()->json(['nao_lidas' => $request->user()->unreadNotifications()->count()]);
    }
}
