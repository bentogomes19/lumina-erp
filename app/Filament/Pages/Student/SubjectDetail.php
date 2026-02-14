<?php

namespace App\Filament\Pages\Student;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SubjectDetail extends Page
{
    protected static ?string $navigationLabel = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'subject-detail';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';

    public ?int $subjectId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.subject-detail';
    }

    public function mount(?int $subject = null): void
    {
        $this->subjectId = $subject ?? request()->query('subject');

        if (! $this->subjectId) {
            abort(404, 'Disciplina não especificada');
        }

        $student = Student::where('user_id', auth()->id())->first();
        if (! $student) {
            abort(403, 'Estudante não encontrado');
        }

        // Verify student is enrolled in a class that has this subject
        $hasAccess = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->whereHas('subjects', fn ($q) => $q->where('subjects.id', $this->subjectId))
            ->exists();

        if (! $hasAccess) {
            abort(403, 'Você não tem acesso a esta disciplina');
        }
    }

    public function getTitle(): string
    {
        $subject = Subject::find($this->subjectId);
        return $subject?->name ?? 'Detalhes da Disciplina';
    }

    /**
     * Get comprehensive subject data: info, teacher, grades, attendance, lessons
     */
    public function getSubjectData(): array
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (! $student || ! $this->subjectId) {
            return $this->emptyData();
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->whereHas('subjects', fn ($q) => $q->where('subjects.id', $this->subjectId))
            ->with(['schoolYear', 'gradeLevel'])
            ->first();

        if (! $currentClass) {
            return $this->emptyData();
        }

        $subject = Subject::with(['gradeLevels'])->find($this->subjectId);

        if (! $subject) {
            return $this->emptyData();
        }

        // Teacher assignment
        $assignment = TeacherAssignment::where('class_id', $currentClass->id)
            ->where('subject_id', $subject->id)
            ->with('teacher')
            ->first();

        // Weekly hours from pivot
        $gradeLevelPivot = $subject->gradeLevels
            ->where('id', $currentClass->grade_level_id)
            ->first();
        $hoursWeekly = $gradeLevelPivot?->pivot?->hours_weekly;

        // Grades
        $grades = Grade::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->where('subject_id', $subject->id)
            ->orderBy('term', 'asc')
            ->orderBy('assessment_type', 'asc')
            ->get();

        $termAverages = [];
        foreach (['b1', 'b2', 'b3', 'b4'] as $term) {
            $termGrades = $grades->filter(fn ($g) => $g->term?->value === $term);
            $termAverages[$term] = [
                'average' => $termGrades->isNotEmpty() ? round($termGrades->avg('score'), 1) : null,
                'grades' => $termGrades,
            ];
        }

        $overallAverage = $grades->isNotEmpty() ? round($grades->avg('score'), 1) : null;

        // Attendance
        $attendances = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->where('subject_id', $subject->id)
            ->get();

        $totalClasses = $attendances->count();
        $presences = $attendances->where('status', 'present')->count();
        $absences = $attendances->where('status', 'absent')->count();
        $lates = $attendances->where('status', 'late')->count();
        $attendancePercent = $totalClasses > 0
            ? round((($presences + $lates) / $totalClasses) * 100, 1)
            : null;

        // Lessons with attendance status
        $lessons = Lesson::where('class_id', $currentClass->id)
            ->where('subject_id', $subject->id)
            ->with(['teacher', 'schoolYear'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($lesson) use ($student) {
                // Get attendance for this lesson
                $attendance = Attendance::where('student_id', $student->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                $lesson->student_attendance = $attendance;
                return $lesson;
            });

        // Statistics by month
        $monthlyStats = $attendances->groupBy(function ($att) {
            return $att->created_at?->format('Y-m') ?? 'unknown';
        })->map(function ($group) {
            return [
                'total' => $group->count(),
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'late' => $group->where('status', 'late')->count(),
                'percent' => $group->count() > 0
                    ? round((($group->where('status', 'present')->count() + $group->where('status', 'late')->count()) / $group->count()) * 100, 1)
                    : 0,
            ];
        });

        return [
            'student' => $student,
            'currentClass' => $currentClass,
            'subject' => $subject,
            'teacher' => $assignment?->teacher,
            'hours_weekly' => $hoursWeekly,
            'grades' => $grades,
            'term_averages' => $termAverages,
            'overall_average' => $overallAverage,
            'total_classes' => $totalClasses,
            'presences' => $presences,
            'absences' => $absences,
            'lates' => $lates,
            'attendance_percent' => $attendancePercent,
            'lessons' => $lessons,
            'monthly_stats' => $monthlyStats,
            'syllabus' => $gradeLevelPivot?->pivot?->syllabus,
            'objectives' => $gradeLevelPivot?->pivot?->objectives,
        ];
    }

    private function emptyData(): array
    {
        return [
            'student' => null,
            'currentClass' => null,
            'subject' => null,
            'teacher' => null,
            'hours_weekly' => null,
            'grades' => collect(),
            'term_averages' => [],
            'overall_average' => null,
            'total_classes' => 0,
            'presences' => 0,
            'absences' => 0,
            'lates' => 0,
            'attendance_percent' => null,
            'lessons' => collect(),
            'monthly_stats' => collect(),
            'syllabus' => null,
            'objectives' => null,
        ];
    }
}
