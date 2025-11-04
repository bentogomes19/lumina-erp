<?php

namespace Database\Seeders\Academic;

use App\Models\Assessment;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assignments = TeacherAssignment::all();

        foreach ($assignments as $assignment) {
            for ($i = 1; $i <= 3; $i++) {
                Assessment::create([
                    'title'       => "Avaliação {$i} - {$assignment->subject->name}",
                    'scheduled_at'=> Carbon::now()->addDays(rand(1, 30)),
                    'weight'      => 2.0,
                    'description' => 'Avaliação automática de seed.',
                    'date'        => Carbon::now()->addDays(rand(1, 30))->toDateString(),
                    'class_id'    => $assignment->class_id,
                    'subject_id'  => $assignment->subject_id,
                    'teacher_id'  => $assignment->teacher_id,
                ]);
            }
        }
    }
}
