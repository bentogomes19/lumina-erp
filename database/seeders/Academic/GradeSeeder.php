<?php

namespace Database\Seeders\Academic;

use App\Enums\AssessmentType;
use App\Enums\Term;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enrollments = Enrollment::with(['class.subjects'])->get();

        foreach ($enrollments as $enr) {
            // Get subjects for this specific class
            $classSubjects = $enr->class->subjects;
            
            if ($classSubjects->isEmpty()) {
                continue; // Skip if class has no subjects assigned
            }

            foreach ([Term::B1, Term::B2] as $term) {
                foreach ($classSubjects as $subject) {
                    foreach ([1,2] as $seq) {
                        Grade::updateOrCreate([
                            'enrollment_id'   => $enr->id,
                            'student_id'      => $enr->student_id,
                            'class_id'        => $enr->class_id,
                            'subject_id'      => $subject->id,
                            'term'            => $term->value,
                            'assessment_type' => AssessmentType::TEST->value,
                            'sequence'        => $seq,
                        ], [
                            'score'       => rand(50, 100) / 10, // 5.0–10.0
                            'max_score'   => 10,
                            'weight'      => 1,
                            'date_recorded' => now()->subDays(rand(1,60)),
                            'origin'      => 'manual',
                        ]);
                    }
                }
            }
        }
    }
}
