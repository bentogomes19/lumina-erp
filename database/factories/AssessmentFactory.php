<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory {
    public function definition(): array {
        return ['class_id' => SchoolClass::factory(), 'subject_id' => Subject::factory(), 'teacher_id' => Teacher::factory(), 'school_year_id' => fn (array $a) => SchoolClass::findOrFail($a['class_id'])->school_year_id, 'title' => 'Prova do 1º bimestre', 'assessment_type' => 'test', 'weight' => 2, 'max_score' => 10, 'date' => fn (array $a) => SchoolClass::findOrFail($a['class_id'])->schoolYear->starts_at->addMonth()->toDateString(), 'scheduled_at' => fn (array $a) => $a['date'] . ' 09:00:00', 'status' => 'open'];
    }
}
