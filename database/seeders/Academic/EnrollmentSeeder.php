<?php

namespace Database\Seeders\Academic;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes  = SchoolClass::all();
        $students = Student::all();        // Professor
        $teacher = User::firstOrCreate(
            ['email' => 'professor@lumina.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Professor Exemplo',
                'password' => Hash::make('123456'),
                'active' => true,
            ]
        );
        $teacher->syncRoles('teacher');


        foreach ($students as $student) {
            $class = $classes->random();

            Enrollment::create([
                'student_id'      => $student->id,
                'class_id'        => $class->id,
                'enrollment_date' => now()->subDays(rand(10, 90)),
                'roll_number'     => rand(1, 40),
                'status'          => 'Ativa',
            ]);
        }
    }
}
