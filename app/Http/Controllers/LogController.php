<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public const ACOES = ['created' => 'Criação', 'updated' => 'Alteração', 'deleted' => 'Exclusão', 'restored' => 'Restauração'];

    public function index(Request $request): View
    {
        $request->validate([
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'user_id' => ['nullable', 'integer'],
            'acao' => ['nullable', 'in:'.implode(',', array_keys(self::ACOES))],
            'modelo' => ['nullable', 'string', 'max:60'],
        ]);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->input('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->input('acao'), fn ($q, $a) => $q->where('acao', $a))
            ->when($request->input('modelo'), fn ($q, $m) => $q->where('model_type', 'App\\Models\\'.$m))
            ->when($request->input('model_id'), fn ($q, $id) => $q->where('model_id', (int) $id))
            ->when($request->input('de'), fn ($q, $d) => $q->where('created_at', '>=', $d.' 00:00:00'))
            ->when($request->input('ate'), fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'acoes' => self::ACOES,
            'usuarios' => User::withTrashed()->orderBy('name')->pluck('name', 'id')->all(),
            'modelos' => ActivityLog::query()->distinct()->orderBy('model_type')->pluck('model_type')
                ->filter()->mapWithKeys(fn ($m) => [class_basename($m) => class_basename($m)])->all(),
        ]);
    }
}
