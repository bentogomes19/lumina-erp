<?php

namespace App\Filament\Pages\Student;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Services\GradeCalculationService;
use App\Support\PermissionAccess;
use Filament\Pages\Page;

class MySubjects extends Page
{
    protected static ?string $navigationLabel = 'Minhas Disciplinas';

    protected static ?string $title = 'Minhas Disciplinas';

    protected static ?string $slug = 'my-subjects';

    protected static string|null|\BackedEnum $navigationIcon = 'fas-book-open';

    protected static ?int $navigationSort = 3;

    /**
     * Determina se a página deve ser registrada na navegação.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return PermissionAccess::can('student.subjects.view');
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     */
    public static function canAccess(): bool
    {
        return PermissionAccess::can('student.subjects.view');
    }

    /**
     * Retorna o nome da visualização usada pela página.
     */
    public function getView(): string
    {
        return 'filament.pages.student.my-subjects';
    }

    /**
     * Retorna o aluno, a turma atual e as disciplinas com professor, notas e frequência.
     */
    public function getPageData(): array
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (! $student) {
            return ['student' => null, 'currentClass' => null, 'subjects' => collect(), 'stats' => []];
        }

        $currentClass = $student->currentClass();

        if (! $currentClass) {
            return ['student' => $student, 'currentClass' => null, 'subjects' => collect(), 'stats' => []];
        }

        $currentClass->load('subjects.gradeLevels');

        $subjects = $currentClass->subjects;

        /* Carrega as atribuições de professores desta turma. */
        $assignments = TeacherAssignment::where('class_id', $currentClass->id)
            ->with(['teacher' => fn ($query) => $query->withTrashed()])
            ->get()
            ->keyBy('subject_id');

        /* Carrega as notas do aluno nesta turma. */
        $grades = Grade::published()->where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get()
            ->groupBy('subject_id');
        $gradeService = app(GradeCalculationService::class);

        /* Carrega a frequência do aluno nesta turma. */
        $attendances = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get()
            ->groupBy('subject_id');

        /* Complementa os dados de cada disciplina. */
        $enriched = $subjects->map(function ($subject) use ($assignments, $grades, $attendances, $currentClass, $gradeService) {
            $assignment = $assignments->get($subject->id);
            $subjectGrades = $grades->get($subject->id, collect());
            $subjectAttendances = $attendances->get($subject->id, collect());

            /* Carga horária semanal obtida da tabela grade_level_subject. */
            $gradeLevelPivot = $subject->gradeLevels
                ->where('id', $currentClass->grade_level_id)
                ->first();
            $hoursWeekly = $gradeLevelPivot?->pivot?->hours_weekly;

            $report = $gradeService->subjectReport($subjectGrades);
            $termAverages = collect($report['terms'])
                ->mapWithKeys(fn (array $term, string $key) => [$key => $term['final_average']])
                ->all();
            $overallAverage = $report['overall_average'];

            /* Estatísticas de frequência. */
            $totalClasses = $subjectAttendances->count();
            $presences = $subjectAttendances->filter(fn ($attendance) => $attendance->status?->countsAsPresent())->count();
            $absences = $subjectAttendances->where('status', 'absent')->count();
            $attendancePercent = $totalClasses > 0
                ? round(($presences / $totalClasses) * 100, 1)
                : null;

            $subject->teacher_name = $assignment?->teacher?->name;
            $subject->teacher_is_inactive = $assignment?->teacher?->trashed() ?? false;
            $subject->hours_weekly = $hoursWeekly;
            $subject->term_averages = $termAverages;
            $subject->overall_average = $overallAverage;
            $subject->attendance_percent = $attendancePercent;
            $subject->total_classes = $totalClasses;
            $subject->presences = $presences;
            $subject->absences = $absences;

            return $subject;
        })->sortBy(fn ($s) => $s->category?->value.$s->name);

        /* Estatísticas gerais. */
        $allGrades = $grades->flatten(1);
        $allAttendance = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get();

        $stats = [
            'total_subjects' => $subjects->count(),
            'overall_average' => $gradeService->overallAverage($allGrades),
            'attendance_percent' => $allAttendance->count() > 0
                ? round(($allAttendance->filter(fn ($attendance) => $attendance->status?->countsAsPresent())->count() / $allAttendance->count()) * 100, 1)
                : null,
            'total_hours_weekly' => $enriched->sum('hours_weekly'),
        ];

        return [
            'student' => $student,
            'currentClass' => $currentClass,
            'subjects' => $enriched,
            'stats' => $stats,
        ];
    }
}
