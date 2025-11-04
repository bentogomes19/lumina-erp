<?php

namespace Database\Seeders\Academic;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = Teacher::all();
        $classes  = SchoolClass::all();
        $subjects = Subject::all();

        if ($teachers->isEmpty() || $classes->isEmpty() || $subjects->isEmpty()) {
            $this->command->warn('TeacherAssignmentSeeder: faltam teachers, classes ou subjects. Pulei o seeder.');
            return;
        }

        foreach ($classes as $class) {
            // escolhe de 3 a 5 disciplinas para a turma (ajusta se quiser)
            $classSubjects = $subjects->random(
                min($subjects->count(), random_int(3, 5))
            );

            foreach ($classSubjects as $subject) {
                $teacher = $teachers->random();

                TeacherAssignment::firstOrCreate([
                    'teacher_id' => $teacher->id,
                    'class_id'   => $class->id,
                    'subject_id' => $subject->id,
                ]);
            }
        }
    }
}
