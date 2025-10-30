<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@lumina.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Administrador',
                'password' => Hash::make('123456'),
                'active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Professor
        $teacher = User::firstOrCreate(
            ['email' => 'professor@lumina.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Professor Exemplo',
                'password' => Hash::make('123456'),
                'active' => true,
            ]
        );
        $teacher->assignRole('teacher');

        // Aluno
        $student = User::firstOrCreate(
            ['email' => 'aluno@lumina.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Aluno Exemplo',
                'password' => Hash::make('123456'),
                'active' => true,
            ]
        );
        $student->assignRole('student');
    }
}
