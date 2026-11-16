<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Confere o conteúdo binário do arquivo (não a extensão nem o mime informado pelo navegador):
 * só aceita JPEG, PNG ou WEBP que o PHP consiga ler como imagem.
 */
class ImagemReal implements ValidationRule
{
    private const TIPOS = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return; // outras regras tratam
        }

        $info = @getimagesize($value->getRealPath());

        if ($info === false || ! in_array($info[2], self::TIPOS, true) || $info[0] < 1 || $info[1] < 1) {
            $fail('O arquivo :attribute não é uma imagem JPG, PNG ou WEBP válida.');
        }
    }
}
