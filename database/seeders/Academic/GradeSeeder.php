<?php

namespace Database\Seeders\Academic;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::classes() as $class) {
            $enrollments = Enrollment::where('class_id', $class->id)->with('student')->get();
            $assessments = Assessment::where('class_id', $class->id)->where('title', 'like', 'B% |%')
                ->whereDate('date', '<', today())->with('teacher')->get();
            $existing = Grade::whereIn('assessment_id', $assessments->pluck('id'))->get(['assessment_id', 'student_id'])
                ->keyBy(fn ($g) => $g->assessment_id.'-'.$g->student_id);
            $rows = [];
            foreach ($assessments as $assessment) {
                preg_match('/B([1-4])/', $assessment->title, $match);
                $term = 'b'.$match[1];
                $type = $assessment->assessment_type === 'test' ? 'test' : 'work';
                foreach ($enrollments as $enrollment) {
                    if ($existing->has($assessment->id.'-'.$enrollment->student_id)
                        || $enrollment->enrollment_date->gt($assessment->date)) { continue; }
                    $rows[] = [
                        'assessment_id' => $assessment->id, 'student_id' => $enrollment->student_id,
                        'enrollment_id' => $enrollment->id, 'class_id' => $class->id,
                        'subject_id' => $assessment->subject_id, 'teacher_id' => $assessment->teacher_id,
                        'term' => $term, 'assessment_type' => $type, 'sequence' => 1,
                        'score' => SchoolPopulation::number($enrollment->student->registration_number.'-'.$class->code.'-'.$assessment->title.'-'.$assessment->subject_id, SchoolPopulation::closed($class->schoolYear) ? 60 : 25, 100) / 10,
                        'max_score' => 10, 'weight' => $assessment->weight,
                        'date_recorded' => $assessment->date->toDateString(), 'origin' => 'manual',
                        'posted_by' => $assessment->teacher->user_id, 'locked_at' => $assessment->scheduled_at,
                        'created_at' => $assessment->scheduled_at, 'updated_at' => $assessment->scheduled_at,
                    ];
                }
            }
            foreach (array_chunk($rows, 300) as $chunk) { DB::table('grades')->insert($chunk); }
        }
    }
}
