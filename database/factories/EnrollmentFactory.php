<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory {
    public function definition(): array {
        return ['student_id' => Student::factory(), 'class_id' => SchoolClass::factory(), 'school_year_id' => fn (array $a) => SchoolClass::findOrFail($a['class_id'])->school_year_id, 'enrollment_date' => fn (array $a) => SchoolClass::findOrFail($a['class_id'])->schoolYear->starts_at, 'status' => 'Ativa'];
    }
}
