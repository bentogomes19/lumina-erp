<?php

namespace Database\Seeders\Academic;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::limit(10)->get();
        foreach ($students as $s) {
            $class = $s->classes()->first();
            if (! $class) continue;

            foreach (range(0,6) as $i) {
                Attendance::updateOrCreate([
                    'student_id' => $s->id,
                    'class_id'   => $class->id,
                    'subject_id' => null,
                    'date'       => now()->subDays($i)->toDateString(),
                ], ['status' => ['present','absent','late'][rand(0,2)]]);
            }
        }
    }
}
