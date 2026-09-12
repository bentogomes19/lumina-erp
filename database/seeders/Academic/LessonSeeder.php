<?php

namespace Database\Seeders\Academic;

use App\Models\Lesson;
use App\Models\SchoolHoliday;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class LessonSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::classes() as $class) {
            $assignments = $class->teacherAssignments->keyBy('subject.code');
            $holidays = SchoolHoliday::where('school_year_id', $class->school_year_id)->where('is_active', true)->get();
            $existing = Lesson::withTrashed()->where('class_id', $class->id)->get(['date', 'start_time'])
                ->mapWithKeys(fn ($lesson) => [$lesson->date->toDateString().'|'.$lesson->start_time->format('H:i:s') => true]);
            $rows = [];
            $slots = $class->shift->value === 'morning'
                ? [['07:00', '07:50'], ['07:50', '08:40'], ['08:40', '09:30'], ['09:50', '10:40'], ['10:40', '11:30']]
                : [['13:00', '13:50'], ['13:50', '14:40'], ['14:40', '15:30'], ['15:50', '16:40'], ['16:40', '17:30']];
            for ($date = $class->schoolYear->starts_at->copy(); $date->lte($class->schoolYear->ends_at); $date->addDay()) {
                if ($date->isWeekend() || $holidays->contains(fn ($h) => $date->betweenIncluded($h->start_date, $h->end_date))) { continue; }
                foreach ($slots as $slot => [$start, $end]) {
                    // Rotação evita o mesmo professor em duas turmas no mesmo horário.
                    $subject = SchoolPopulation::SUBJECTS[($slot + $class->gradeLevel->display_order - 1) % 5];
                    $assignment = $assignments->get($subject);
                    $key = $class->id.'-'.$date->toDateString().'-'.$start;
                    $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'lumina-school:'.$key);
                    if ($existing->has($date->toDateString().'|'.$start.':00')) { continue; }
                    $past = $date->lt(today());
                    $taken = $past && (SchoolPopulation::closed($class->schoolYear) || $date->lt(today()->subDays(2)));
                    $rows[] = [
                        'uuid' => $uuid, 'teacher_id' => $assignment->teacher_id, 'class_id' => $class->id,
                        'subject_id' => $assignment->subject_id, 'school_year_id' => $class->school_year_id,
                        'date' => $date->toDateString(), 'start_time' => $start, 'end_time' => $end, 'duration_minutes' => 50,
                        'topic' => $assignment->subject->name.' | Unidade '.(int) ceil($date->month / 2),
                        'content' => $past ? 'Revisão, atividade orientada e discussão dos exercícios da unidade.' : null,
                        'objectives' => 'Aplicar os conceitos da unidade em situações do cotidiano.',
                        'homework' => $past ? 'Revisar a atividade e registrar as dúvidas para a próxima aula.' : null,
                        'status' => $past ? 'completed' : 'scheduled', 'attendance_taken' => $taken,
                        'attendance_taken_at' => $taken ? $date->toDateString().' '.$end : null,
                        'attendance_taken_by' => $taken ? $assignment->teacher->user_id : null,
                        'created_at' => $class->schoolYear->starts_at->toDateTimeString(),
                        'updated_at' => $past ? $date->toDateTimeString() : $class->schoolYear->starts_at->toDateTimeString(),
                    ];
                }
            }
            foreach (array_chunk($rows, 300) as $chunk) { DB::table('lessons')->insert($chunk); }
        }
    }
}
