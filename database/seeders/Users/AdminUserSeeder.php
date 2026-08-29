<?php

namespace Database\Seeders\Users;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder {

    /**
     * Cria o usuário administrador inicial.
     *
     * @return void
     */
    public function run(): void {

        /* Admin. */
        $admin = User::firstOrCreate(
            ['email' => 'admin@lumina.com'],
            [
                'uuid'     => (string) Str::uuid(),
                'name'     => 'Administrador',
                'password' => Hash::make('123456'),
                'active'   => true,
            ]
        );
        $admin->syncRoles('admin');

    }
}
