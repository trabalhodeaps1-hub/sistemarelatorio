<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        $master = config('sistema.master');

        User::firstOrCreate(
            ['email' => $master['email']],
            [
                'name' => $master['name'],
                'matricula' => $master['matricula'],
                'password' => $master['password'],
                'role' => Role::Master,
                'ativo' => true,
            ],
        );
    }
}
