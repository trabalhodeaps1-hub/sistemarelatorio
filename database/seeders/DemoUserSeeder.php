<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['Técnico Demo', 'tecnico@autorelatorio.local', 'T0001', Role::Tecnico],
            ['Técnico Demo 2', 'tecnico2@autorelatorio.local', 'T0002', Role::Tecnico],
            ['Supervisor Demo', 'supervisor@autorelatorio.local', 'S0001', Role::Supervisor],
            ['Gerente Demo', 'gerente@autorelatorio.local', 'G0001', Role::Gerente],
        ];

        foreach ($usuarios as [$nome, $email, $matricula, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $nome, 'matricula' => $matricula, 'password' => 'password', 'role' => $role, 'ativo' => true],
            );
        }
    }
}
