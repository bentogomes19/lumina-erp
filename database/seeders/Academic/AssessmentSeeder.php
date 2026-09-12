<?php

namespace Database\Seeders\Academic;

use App\Models\Assessment;
use App\Models\Lesson;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::classes() as $class) {
            foreach ($class->teacherAssignments as $assignment) {
                foreach (range(1, 4) as $term) {
                    foreach ([1, 2] as $sequence) {
                        $date = SchoolPopulation::assessmentDate($class->schoolYear->year, $term, $sequence);
                        $lesson = Lesson::where('class_id', $class->id)->where('subject_id', $assignment->subject_id)
                            ->whereDate('date', '>=', $date)->orderBy('date')->orderBy('start_time')->firstOrFail();
                        $date = $lesson->date->copy()->setTimeFromTimeString($lesson->start_time->format('H:i:s'));
                        Assessment::updateOrCreate([
                            'class_id' => $class->id, 'subject_id' => $assignment->subject_id,
                            'title' => "B{$term} | ".($sequence === 1 ? 'Prova' : 'Trabalho'),
                        ], [
                            'teacher_id' => $assignment->teacher_id, 'school_year_id' => $class->school_year_id,
                            'assessment_type' => $sequence === 1 ? 'test' : 'work',
                            'date' => $date->toDateString(), 'scheduled_at' => $date,
                            'weight' => $sequence === 1 ? 2 : 1, 'max_score' => 10,
                            'description' => 'Avaliação prevista no calendário do período letivo.',
                            'status' => SchoolPopulation::closed($class->schoolYear) ? 'closed' : 'open',
                        ]);
                    }
                }
            }
        }
    }
}
