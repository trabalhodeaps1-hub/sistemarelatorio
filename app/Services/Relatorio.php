<?php

namespace App\Services;

/** Resultado genérico de relatório: renderizado igual na prévia HTML e no PDF. */
class Relatorio
{
    /**
     * @param  array<string, string>  $colunas  chave => rótulo
     * @param  array<int, array<string, mixed>>  $linhas
     * @param  array<string, string|int|float>  $resumo  rótulo => valor
     * @param  array<int, string>  $numericas  colunas alinhadas à direita
     */
    public function __construct(
        public string $titulo,
        public string $descricao,
        public array $colunas,
        public array $linhas,
        public array $resumo = [],
        public array $numericas = [],
        public string $orientacao = 'portrait',
        public ?Relatorio $secundario = null,
    ) {}
}
