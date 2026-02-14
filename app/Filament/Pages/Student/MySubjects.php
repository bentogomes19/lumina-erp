<?php

namespace App\Filament\Pages\Student;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Filament\Pages\Page;

class MySubjects extends Page
{
    protected static ?string $navigationLabel = 'Minhas Disciplinas';
    protected static ?string $title = 'Minhas Disciplinas';
    protected static ?string $slug = 'my-subjects';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.my-subjects';
    }

    /**
     * Returns student, current class, and enriched subjects with teacher, grades, and attendance.
     */
    public function getPageData(): array
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (! $student) {
            return ['student' => null, 'currentClass' => null, 'subjects' => collect(), 'stats' => []];
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->with(['schoolYear', 'gradeLevel', 'subjects.gradeLevels'])
            ->first();

        if (! $currentClass) {
            return ['student' => $student, 'currentClass' => null, 'subjects' => collect(), 'stats' => []];
        }

        $subjects = $currentClass->subjects;

        // Load teacher assignments for this class
        $assignments = TeacherAssignment::where('class_id', $currentClass->id)
            ->with('teacher')
            ->get()
            ->keyBy('subject_id');

        // Load grades for this student in this class
        $grades = Grade::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get()
            ->groupBy('subject_id');

        // Load attendance for this student in this class
        $attendances = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get()
            ->groupBy('subject_id');

        // Enrich each subject
        $enriched = $subjects->map(function ($subject) use ($assignments, $grades, $attendances, $currentClass) {
            $assignment = $assignments->get($subject->id);
            $subjectGrades = $grades->get($subject->id, collect());
            $subjectAttendances = $attendances->get($subject->id, collect());

            // Weekly hours from grade_level_subject pivot
            $gradeLevelPivot = $subject->gradeLevels
                ->where('id', $currentClass->grade_level_id)
                ->first();
            $hoursWeekly = $gradeLevelPivot?->pivot?->hours_weekly;

            // Grade average per term
            $termAverages = [];
            foreach (['b1', 'b2', 'b3', 'b4'] as $term) {
                $termGrades = $subjectGrades->filter(fn ($g) => $g->term?->value === $term);
                $termAverages[$term] = $termGrades->isNotEmpty()
                    ? round($termGrades->avg('score'), 1)
                    : null;
            }

            $overallAverage = $subjectGrades->isNotEmpty()
                ? round($subjectGrades->avg('score'), 1)
                : null;

            // Attendance stats
            $totalClasses = $subjectAttendances->count();
            $presences = $subjectAttendances->where('status', 'present')->count();
            $absences = $subjectAttendances->where('status', 'absent')->count();
            $attendancePercent = $totalClasses > 0
                ? round(($presences / $totalClasses) * 100, 1)
                : null;

            $subject->teacher_name = $assignment?->teacher?->name;
            $subject->hours_weekly = $hoursWeekly;
            $subject->term_averages = $termAverages;
            $subject->overall_average = $overallAverage;
            $subject->attendance_percent = $attendancePercent;
            $subject->total_classes = $totalClasses;
            $subject->presences = $presences;
            $subject->absences = $absences;

            return $subject;
        })->sortBy(fn ($s) => $s->category?->value . $s->name);

        // Global stats
        $allGrades = Grade::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get();
        $allAttendance = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->get();

        $stats = [
            'total_subjects' => $subjects->count(),
            'overall_average' => $allGrades->isNotEmpty() ? round($allGrades->avg('score'), 1) : null,
            'attendance_percent' => $allAttendance->count() > 0
                ? round(($allAttendance->where('status', 'present')->count() / $allAttendance->count()) * 100, 1)
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
