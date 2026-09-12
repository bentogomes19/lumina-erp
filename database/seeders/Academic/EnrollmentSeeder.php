<?php

namespace Database\Seeders\Academic;

use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        $classes = SchoolPopulation::classes()->keyBy(fn ($class) => $class->schoolYear->year.'-'.$class->gradeLevel->display_order);
        $operator = User::where('email', 'admin@lumina.com')->value('id');
        foreach (Student::whereNotNull('meta->cohort_start_year')->orderBy('registration_number')->get() as $student) {
            $entryYear = (int) $student->meta['cohort_start_year'];
            $previous = null;
            foreach (range(max($entryYear, SchoolPopulation::firstYear()), min($entryYear + 8, now()->year)) as $number) {
                $class = $classes->get($number.'-'.($number - $entryYear + 1));
                if (!$class) { continue; }
                $previous = DB::transaction(function () use ($class, $student, $number, $previous, $operator) {
                    // Uma carga adicional nunca cria uma segunda matrícula no mesmo ano.
                    $existing = Enrollment::where('student_id', $student->id)->where('school_year_id', $class->school_year_id)->first();
                    if ($existing) { return $existing; }
                    $date = "$number-02-01";
                    $enrollment = Enrollment::create([
                        'student_id' => $student->id, 'class_id' => $class->id, 'school_year_id' => $class->school_year_id,
                        'enrollment_date' => $date, 'roll_number' => (int) $student->meta['roster_position'],
                        'registration_number' => "MAT-$number-{$student->id}",
                        'status' => SchoolPopulation::enrollmentStatus($class->schoolYear),
                        'previous_enrollment_id' => $previous?->id, 'operated_by_user_id' => $operator,
                        'created_at' => $date, 'updated_at' => SchoolPopulation::closed($class->schoolYear) ? "$number-12-15" : $date,
                    ]);
                    $enrollment->logs()->where('acao', 'criacao')->update(['created_at' => $date, 'operador_id' => $operator, 'status_novo' => 'Ativa', 'observacao' => 'Matrícula registrada no ingresso do período letivo.']);
                    if (SchoolPopulation::closed($class->schoolYear)) {
                        EnrollmentLog::create(['enrollment_id' => $enrollment->id, 'operador_id' => $operator,
                            'acao' => 'conclusao', 'status_anterior' => 'Ativa', 'status_novo' => 'Completa',
                            'observacao' => 'Ano letivo concluído.', 'created_at' => "$number-12-15"]);
                    }
                    return $enrollment;
                });
            }
        }
    }
}
