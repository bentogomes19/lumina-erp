<?php

namespace Database\Seeders\Academic;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder {

    /**
     * Cria as matrículas acadêmicas iniciais.
     *
     * @return void
     */
    public function run(): void {
        $activeYear = SchoolYear::where('is_active', true)->first();
        $classes    = SchoolClass::query()
            ->when($activeYear, fn ($q) => $q->where('school_year_id', $activeYear->id))
            ->get();
        $students = Student::all();

        if ($classes->isEmpty() || $students->isEmpty()) {
            $this->command?->warn('EnrollmentSeeder: faltam turmas ou alunos, seeder pulado.');
            return;
        }

        foreach ($students as $student) {
            $class = $classes->random();

            Enrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'class_id'   => $class->id,
                ],
                [
                    'enrollment_date' => now()->subDays(rand(10, 90)),
                    'roll_number'     => rand(1, 40),
                    'status'          => 'Ativa',
                ]
            );
        }
    }
}
