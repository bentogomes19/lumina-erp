<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory {

    /**
     * Retorna os dados padrão gerados pela fábrica.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'enrollment_id' => Enrollment::factory(),
            'student_id' => fn (array $a) => Enrollment::findOrFail($a['enrollment_id'])->student_id,
            'class_id' => fn (array $a) => Enrollment::findOrFail($a['enrollment_id'])->class_id,
            'subject_id' => Subject::factory(), 'teacher_id' => Teacher::factory(),
            'score' => $this->faker->randomFloat(1, 0, 10), 'max_score' => 10, 'weight' => 2,
            'term' => 'b1', 'assessment_type' => 'test', 'sequence' => 1, 'origin' => 'manual',
        ];
    }

    public function forAssessment(Assessment $assessment, Enrollment $enrollment): static {
        if ($assessment->class_id !== $enrollment->class_id) {
            throw new \InvalidArgumentException('A avaliação deve pertencer à turma da matrícula.');
        }

        return $this->state(fn () => [
            'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id, 'class_id' => $enrollment->class_id,
            'subject_id' => $assessment->subject_id, 'teacher_id' => $assessment->teacher_id,
            'max_score' => $assessment->max_score, 'weight' => $assessment->weight,
            'score' => $this->faker->randomFloat(1, 0, (float) $assessment->max_score),
            'date_recorded' => $assessment->date,
        ]);
    }
}
