<?php

namespace Database\Seeders\Academic;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        $classes = SchoolClass::query()
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
