<?php

namespace Database\Seeders\Users;

use App\Models\Student;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        // Cada coorte ingressa no 1º ano e cursa os nove anos do fundamental.
        // Inclui egressos para que as turmas dos anos anteriores também tenham alunos.
        foreach (range(SchoolPopulation::firstYear() - 8, now()->year) as $entryYear) {
            for ($position = 1; $position <= SchoolPopulation::studentsPerClass(); $position++) {
                $key = 'ALU-'.$entryYear.'-'.str_pad((string) $position, 3, '0', STR_PAD_LEFT);
                if (Student::where('registration_number', $key)->exists()) {
                    continue;
                }
                $main = $entryYear === now()->year - 4 && $position === 1;
                $email = $main ? 'aluno@lumina.com' : "aluno.$entryYear.$position@lumina.com";
                $data = Student::factory()->enteringIn($entryYear)->make(['user_id' => null, 'email' => $email]);
                $user = SchoolPopulation::account($email, $main ? 'Lucas Henrique Silva' : $data->name, 'student');
                $existing = Student::where('user_id', $user->id)->first();
                // Não substituir um histórico existente: apenas identificar a conta padrão.
                if ($existing) {
                    $meta = is_array($existing->meta) ? $existing->meta : [];
                    // Dados antigos do seeder podiam ter sido persistidos como string JSON.
                    if (isset($meta[0]) && $meta[0] === '[]') {
                        unset($meta[0]);
                    }
                    $existing->update(['meta' => array_merge($meta, ['cohort_start_year' => $entryYear, 'roster_position' => $position])]);
                    continue;
                }
                $data->forceFill([
                    'user_id' => $user->id, 'name' => $user->name, 'registration_number' => $key,
                    'status' => $entryYear + 8 < now()->year ? 'graduated' : 'active',
                    'exit_date' => $entryYear + 8 < now()->year ? ($entryYear + 8).'-12-15' : null,
                    'meta' => ['cohort_start_year' => $entryYear, 'roster_position' => $position],
                ])->save();
            }
        }
    }
}
