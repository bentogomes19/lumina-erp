<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $classes = SchoolClass::all();

        foreach ($students as $student) {
            Enrollment::factory()->create([
                'student_id' => $student->id,
                'class_id' => $classes->random()->id,
            ]);
        }
    }
}
