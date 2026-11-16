<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetarSenhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    public function rules(): array
    {
        return ['nova_senha' => ['required', 'confirmed', Password::min(8)]];
    }

    public function attributes(): array
    {
        return ['nova_senha' => 'nova senha'];
    }
}
