<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra created/updated/deleted em activity_logs, com as mudanças.
 * Campos sensíveis nunca são gravados.
 */
trait RegistraAtividade
{
    protected static array $camposOcultosNoLog = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function bootRegistraAtividade(): void
    {
        static::created(fn (Model $m) => static::registrarAtividade($m, 'created', [
            'novo' => static::limparParaLog($m->getAttributes()),
        ]));

        static::updated(function (Model $m) {
            $mudancas = static::limparParaLog($m->getChanges());
            if (! $mudancas) {
                return;
            }
            $anterior = array_intersect_key(static::limparParaLog($m->getOriginal()), $mudancas);

            static::registrarAtividade($m, 'updated', ['antes' => $anterior, 'depois' => $mudancas]);
        });

        static::deleted(fn (Model $m) => static::registrarAtividade($m, 'deleted', [
            'registro' => static::limparParaLog($m->getAttributes()),
        ]));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => static::registrarAtividade($m, 'restored', []));
        }
    }

    protected static function limparParaLog(array $dados): array
    {
        $tinhaSenha = array_key_exists('password', $dados);

        $dados = array_diff_key($dados, array_flip(static::$camposOcultosNoLog));

        if ($tinhaSenha) {
            $dados['password'] = '[alterada]';
        }

        return array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $dados);
    }

    protected static function registrarAtividade(Model $model, string $acao, array $dados): void
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'acao' => $acao,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'dados' => $dados,
            'ip' => $request?->ip(),
        ]);
    }
}
