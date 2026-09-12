<?php

namespace Database\Seeders\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\SchoolYearStatus;
use App\Models\Assessment;
use App\Models\EnrollmentLog;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/** Conventions shared by realistic school population seeders. */
class SchoolPopulation {
    public const SUBJECTS = ['LP', 'MAT', 'CIE', 'HIS', 'GEO'];

    public static function assertEnvironment(): void {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('A carga de dados escolares só pode ser gerada em local ou testing.');
        }
        if (config('population.history_years') < 5 || config('population.history_years') > 10
            || config('population.students_per_class') < 1 || config('population.students_per_class') > 35) {
            throw new RuntimeException('Configure de 5 a 10 anos de histórico e de 1 a 35 alunos por turma.');
        }
    }

    /** Renames records created by previous versions without replacing their history or relationships. */
    public static function normalizeLegacyRecords(): void {
        DB::transaction(function (): void {
            DB::table('users')->where('email', 'like', '%@demo.lumina.test')
                ->update(['email' => DB::raw("REPLACE(email, '@demo.lumina.test', '@lumina.com')")]);
            DB::table('students')->where('email', 'like', '%@demo.lumina.test')
                ->update(['email' => DB::raw("REPLACE(email, '@demo.lumina.test', '@lumina.com')")]);
            DB::table('teachers')->where('email', 'like', '%@demo.lumina.test')
                ->update(['email' => DB::raw("REPLACE(email, '@demo.lumina.test', '@lumina.com')")]);

            foreach (Student::where('registration_number', 'like', 'DEMO-ALU-%')->get() as $student) {
                $student->registration_number = Str::replaceFirst('DEMO-ALU-', 'ALU-', $student->registration_number);
                $student->save();
            }
            foreach (Student::whereNotNull('meta->demo_entry_year')->get() as $student) {
                $meta = is_array($student->meta) ? $student->meta : [];
                $student->meta = array_filter([
                    ...$meta,
                    'cohort_start_year' => $meta['demo_entry_year'] ?? null,
                    'roster_position' => $meta['demo_position'] ?? null,
                ], fn ($value, $key) => !str_starts_with((string) $key, 'demo_') && $value !== null, ARRAY_FILTER_USE_BOTH);
                $student->save();
            }
            Student::where('name', 'Aluno Exemplo')->update(['name' => 'Lucas Henrique Silva']);
            User::where('email', 'aluno@lumina.com')->where('name', 'Aluno Exemplo')->update(['name' => 'Lucas Henrique Silva']);

            foreach (SchoolClass::where('code', 'like', 'DEMO-%')->get() as $class) {
                if (preg_match('/^DEMO-(\d{4})-(\d+)-A$/', $class->code, $matches)) {
                    $class->code = sprintf('TUR-%s-%02d-A', $matches[1], $matches[2]);
                    $class->name = (int) $matches[2].'° ANO A';
                    $class->save();
                }
            }
            foreach (Teacher::where('employee_number', 'like', 'DEMO-PROF-%')->get() as $teacher) {
                $subject = Str::after($teacher->employee_number, 'DEMO-PROF-');
                $teacher->employee_number = 'PROF-'.$subject;
                $teacher->qualification = 'Licenciatura em '.self::subjectName($subject);
                $teacher->save();
            }
            DB::table('enrollments')->where('registration_number', 'like', 'DEMO-MAT-%')
                ->update(['registration_number' => DB::raw("REPLACE(registration_number, 'DEMO-MAT-', 'MAT-')")]);
            foreach (Assessment::where('title', 'like', 'DEMO%')->get() as $assessment) {
                if (preg_match('/^DEMO\s+—\s+B([1-4])\s+—\s+(Prova|Trabalho)$/u', $assessment->title, $matches)) {
                    $assessment->title = 'B'.$matches[1].' | '.$matches[2];
                    $assessment->description = 'Avaliação prevista no calendário do período letivo.';
                    $assessment->save();
                }
            }
            DB::table('lessons')->where('topic', 'like', '%— unidade %')
                ->update(['topic' => DB::raw("REPLACE(topic, ' — unidade ', ' | Unidade ')")]);
            SchoolHoliday::where('name', 'Recesso de julho (demonstração)')
                ->update(['name' => 'Recesso escolar de julho']);
            EnrollmentLog::where('observacao', 'Matrícula sintética para demonstração.')
                ->update(['observacao' => 'Matrícula registrada no ingresso do período letivo.']);
            EnrollmentLog::where('observacao', 'Conclusão sintética do ano letivo.')
                ->update(['observacao' => 'Ano letivo concluído.']);
            DB::table('attendances')->where('notes', 'Ausência justificada pelo responsável (exemplo).')
                ->update(['notes' => 'Ausência justificada pelo responsável.']);

            self::assertLegacyRecordsRemoved();
        });
    }

    private static function assertLegacyRecordsRemoved(): void {
        $legacyTables = array_keys(array_filter([
            'users' => DB::table('users')->where('email', 'like', '%@demo.lumina.test')->exists(),
            'students' => DB::table('students')->where('registration_number', 'like', 'DEMO-ALU-%')
                ->orWhereNotNull('meta->demo_entry_year')->orWhere('email', 'like', '%@demo.lumina.test')->exists(),
            'classes' => DB::table('classes')->where('code', 'like', 'DEMO-%')
                ->orWhere('name', 'like', '%—%')->exists(),
            'teachers' => DB::table('teachers')->where('employee_number', 'like', 'DEMO-PROF-%')
                ->orWhere('email', 'like', '%@demo.lumina.test')->exists(),
            'enrollments' => DB::table('enrollments')->where('registration_number', 'like', 'DEMO-MAT-%')->exists(),
            'assessments' => DB::table('assessments')->where('title', 'like', 'DEMO%')->exists(),
            'lessons' => DB::table('lessons')->where('topic', 'like', '%— unidade %')->exists(),
            'school_holidays' => DB::table('school_holidays')->where('name', 'Recesso de julho (demonstração)')->exists(),
            'enrollment_logs' => DB::table('enrollment_logs')->whereIn('observacao', [
                'Matrícula sintética para demonstração.', 'Conclusão sintética do ano letivo.',
            ])->exists(),
            'attendances' => DB::table('attendances')->where('notes', 'Ausência justificada pelo responsável (exemplo).')->exists(),
        ]));

        if ($legacyTables !== []) {
            throw new RuntimeException('Há registros antigos que não puderam ser normalizados em: '.implode(', ', $legacyTables).'.');
        }
    }

    public static function firstYear(): int {
        return now()->year - (int) config('population.history_years') + 1;
    }

    public static function years() {
        return SchoolYear::whereBetween('year', [self::firstYear(), now()->year])->orderBy('year')->get();
    }

    public static function classes() {
        return SchoolClass::where('code', 'like', 'TUR-%')
            ->whereHas('schoolYear', fn ($q) => $q->whereBetween('year', [self::firstYear(), now()->year]))
            ->with(['schoolYear.terms', 'gradeLevel', 'teacherAssignments.subject', 'teacherAssignments.teacher'])
            ->orderBy('school_year_id')->orderBy('grade_level_id')->get();
    }

    public static function account(string $email, string $name, string $role): User {
        self::assertEnvironment();
        $user = User::firstOrCreate(['email' => $email], [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'password' => Hash::make(config('population.password')),
            'active' => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    public static function closed(SchoolYear $year): bool {
        return $year->status === SchoolYearStatus::CLOSED;
    }

    public static function enrollmentStatus(SchoolYear $year): string {
        return self::closed($year) ? EnrollmentStatus::COMPLETED->value : EnrollmentStatus::ACTIVE->value;
    }

    public static function assessmentDate(int $year, int $term, int $sequence): Carbon {
        $months = [1 => [3, 4], 2 => [5, 6], 3 => [8, 9], 4 => [10, 11]];
        return Carbon::create($year, $months[$term][$sequence - 1], 15, 9)->nextWeekday();
    }

    public static function number(string $key, int $min, int $max): int {
        return $min + (int) (hexdec(substr(hash('sha256', $key), 0, 7)) % ($max - $min + 1));
    }

    public static function subjectName(string $code): string {
        return match ($code) {
            'LP' => 'Língua Portuguesa', 'MAT' => 'Matemática', 'CIE' => 'Ciências',
            'HIS' => 'História', 'GEO' => 'Geografia', default => $code,
        };
    }
}
