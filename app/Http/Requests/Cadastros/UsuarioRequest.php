<?php

namespace App\Http\Requests\Cadastros;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'matricula' => mb_strtoupper(trim((string) $this->input('matricula'))),
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('usuario')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'matricula' => ['required', 'string', 'max:30', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'role' => ['required', Rule::enum(Role::class)],
            'ativo' => ['boolean'],
            'password' => [$id ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'role' => 'perfil',
            'password' => 'senha',
        ];
    }

    public function dados(): array
    {
        $dados = $this->safe()->except('password');

        if ($this->filled('password')) {
            $dados['password'] = $this->validated('password');
        }

        return $dados;
    }
}
