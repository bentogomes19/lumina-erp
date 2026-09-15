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

        // Professores adicionais permitem uma distribuição compatível com uma escola real.
        $extraNames = [
            'Beatriz Martins', 'Gustavo Fernandes', 'Camila Rocha', 'André Luiz Gomes',
            'Fernanda Nunes', 'Marcelo Moura', 'Aline Carvalho', 'Diego Teixeira',
            'Priscila Freitas', 'Thiago Cardoso', 'Renata Batista', 'Bruno Mendes',
            'Vanessa Castro', 'Leonardo Dias', 'Simone Vieira', 'Rodrigo Campos',
        ];
        $perSubject = SchoolPopulation::teachersPerSubject();
        foreach (SchoolPopulation::SUBJECTS as $subjectIndex => $subject) {
            for ($sequence = 2; $sequence <= $perSubject; $sequence++) {
                $name = $extraNames[(($subjectIndex * 3) + $sequence - 2) % count($extraNames)];
                $email = 'professor.'.strtolower($subject).'.'.$sequence.'@lumina.com';
                $user = SchoolPopulation::account($email, $name, 'teacher');
                Teacher::firstOrCreate(['user_id' => $user->id], [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'name' => $name, 'email' => $email,
                    'employee_number' => 'PROF-'.$subject.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                    'qualification' => 'Licenciatura em '.SchoolPopulation::subjectName($subject),
                    'weekly_workload' => 20 + (($sequence % 3) * 10), 'max_classes' => 8,
                    'status' => 'active',
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
