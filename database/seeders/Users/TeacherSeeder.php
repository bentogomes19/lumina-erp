<?php

namespace Database\Seeders\Users;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use App\Models\User;
use Database\Factories\TeacherFactory;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Garante pelo menos um professor "fixo"
        $mainTeacherUser = User::firstOrCreate(
            ['email' => 'professor@lumina.com'],
            [
                'uuid'     => (string) Str::uuid(),
                'name'     => 'Professor Exemplo',
                'password' => Hash::make('password'),
                'active'   => true,
            ]
        );
        $mainTeacherUser->assignRole('teacher');

        // 2) Cria mais usuários professores se quiser massa de teste
        if (User::role('teacher')->count() < 22) {
            $extraUsers = User::factory()->count(21)->create();

            foreach ($extraUsers as $user) {
                $user->assignRole('teacher');
            }
        }

        // 3) Pra cada user com role teacher, garantir um Teacher vinculado
        $teacherUsers = User::role('teacher')->get();

        foreach ($teacherUsers as $user) {
            // monta os dados padrão do professor com base no usuário
            $teacherData = TeacherFactory::new()->make([
                'user_id' => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
            ])->toArray();

            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                $teacherData
            );
        }
    }
}
