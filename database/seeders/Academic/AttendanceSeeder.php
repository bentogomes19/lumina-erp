<?php

namespace Database\Seeders\Academic;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::classes() as $class) {
            $enrollments = Enrollment::where('class_id', $class->id)->with('student')->get();
            Lesson::where('class_id', $class->id)->where('status', 'completed')->where('attendance_taken', true)
                ->orderBy('id')->chunkById(100, function ($lessons) use ($enrollments) {
                    $existing = Attendance::whereIn('lesson_id', $lessons->pluck('id'))->get(['student_id', 'lesson_id'])
                        ->keyBy(fn ($a) => $a->lesson_id.'-'.$a->student_id);
                    $rows = [];
                    foreach ($lessons as $lesson) {
                        foreach ($enrollments as $enrollment) {
                            if ($existing->has($lesson->id.'-'.$enrollment->student_id)
                                || $enrollment->enrollment_date->gt($lesson->date)) { continue; }
                            $value = SchoolPopulation::number($enrollment->student->registration_number.'-'.$lesson->uuid, 1, 100);
                            $status = match (true) { $value <= 88 => 'present', $value <= 93 => 'late', $value <= 96 => 'excused', default => 'absent' };
                            $rows[] = [
                                'student_id' => $enrollment->student_id, 'lesson_id' => $lesson->id,
                                'class_id' => $lesson->class_id, 'subject_id' => $lesson->subject_id,
                                'date' => $lesson->date->toDateString(), 'time' => $lesson->start_time->format('H:i:s'),
                                'status' => $status, 'notes' => $status === 'excused' ? 'Ausência justificada pelo responsável.' : null,
                                'recorded_by' => $lesson->attendance_taken_by,
                                'created_at' => $lesson->attendance_taken_at, 'updated_at' => $lesson->attendance_taken_at,
                            ];
                        }
                    }
                    foreach (array_chunk($rows, 300) as $chunk) { DB::table('attendances')->insert($chunk); }
                });
        }
    }
}
