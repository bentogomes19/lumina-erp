<?php

namespace Database\Seeders\Users;

use App\Models\Teacher;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        $names = ['Ana Paula Ribeiro', 'Carlos Eduardo Santos', 'Mariana Costa', 'Rafael Oliveira', 'Juliana Almeida'];
        foreach (SchoolPopulation::SUBJECTS as $index => $subject) {
            $email = $index === 0 ? 'professor@lumina.com' : 'professor.'.strtolower($subject).'@lumina.com';
            $user = SchoolPopulation::account($email, $names[$index], 'teacher');
            if (!Teacher::where('user_id', $user->id)->exists()) {
                Teacher::factory()->employedSince(SchoolPopulation::firstYear() - 2)->create([
                    'user_id' => $user->id, 'name' => $user->name, 'email' => $email,
                    'employee_number' => 'PROF-'.$subject, 'qualification' => 'Licenciatura em '.SchoolPopulation::subjectName($subject),
                    'weekly_workload' => 40, 'max_classes' => 10,
                ]);
            }
        }
        $user = SchoolPopulation::account('professor.historico@lumina.com', 'Roberto Lima', 'teacher');
        if (!Teacher::where('user_id', $user->id)->exists()) {
            Teacher::factory()->employedSince(SchoolPopulation::firstYear() - 3)->create([
                'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'employee_number' => 'PROF-ANTIGO', 'status' => 'terminated',
                'termination_date' => (now()->year - 1).'-12-15',
            ]);
        }
    }
}
