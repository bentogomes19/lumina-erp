<?php

namespace Database\Seeders\Users;

use App\Models\Student;
use App\Models\User;
use Database\Factories\StudentFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Garante pelo menos um aluno "fixo"
        $mainStudentUser = User::firstOrCreate(
            ['email' => 'aluno@lumina.com'],
            [
                'uuid'     => (string) Str::uuid(),
                'name'     => 'Aluno Exemplo',
                'password' => Hash::make('password'),
                'active'   => true,
            ]
        );
        $mainStudentUser->assignRole('student');

        // 2) Cria mais usuários alunos se quiser massa
        if (User::role('student')->count() < 80) {
            $extraUsers = User::factory()->count(80)->create();

            foreach ($extraUsers as $user) {
                $user->assignRole('student');
            }
        }

        // 3) Pra cada user com role student, garantir um Student vinculado
        $studentUsers = User::role('student')->get();

        foreach ($studentUsers as $user) {
            $studentData = StudentFactory::new()->make([
                'user_id' => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
            ])->toArray();

            Student::firstOrCreate(
                ['user_id' => $user->id],
                $studentData
            );
        }
    }
}
