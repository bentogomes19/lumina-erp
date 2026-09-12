<?php

namespace Database\Seeders\Academic;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::classes() as $class) {
            foreach ($class->subjects as $subject) {
                $code = $subject->code === 'LP' && $class->schoolYear->year < now()->year ? 'ANTIGO' : $subject->code;
                $teacher = Teacher::where('employee_number', 'PROF-'.$code)->first();
                // Contas padrão pré-existentes podem ter outro número funcional.
                if (!$teacher && $subject->code === 'LP') {
                    $teacher = Teacher::whereHas('user', fn ($q) => $q->where('email', 'professor@lumina.com'))->first();
                }
                if (!$teacher) {
                    throw new \RuntimeException('Professor não encontrado para a disciplina '.$subject->code);
                }
                TeacherAssignment::firstOrCreate(['class_id' => $class->id, 'subject_id' => $subject->id], ['teacher_id' => $teacher->id]);
            }
        }
    }
}
