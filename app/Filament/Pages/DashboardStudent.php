<?php

namespace App\Filament\Pages;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Lesson;
use App\Services\GradeCalculationService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class DashboardStudent extends Page
{
    protected static ?string $navigationLabel              = 'Painel do Aluno';
    protected static ?string $title                        = 'Portal do Aluno';
    protected static ?string $slug                         = 'dashboard-student';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort                  = 0;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.dashboard-student';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * Retorna todos os dados necessários para o blade do painel.
     * Os dados são cacheados por 5 minutos por aluno para evitar queries repetitivas.
     */
    public function getPageData(): array
    {
        $student = auth()->user()?->student;

        if (!$student) {
            return $this->emptyData();
        }

        $cacheKey = "dashboard_student_{$student->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($student) {
            $currentClass = $student->classes()
                ->whereHas('schoolYear', fn($q) => $q->where('is_active', true))
                ->with(['schoolYear', 'gradeLevel'])
                ->first();

            if (!$currentClass) {
                return array_merge($this->emptyData(), ['student' => $student]);
            }

            // Frequência geral
            $attendanceData = Attendance::calculateFrequency($student->id, $currentClass->id);

            // Médias por disciplina → média geral
            $allGrades = Grade::where('student_id', $student->id)
                ->where('class_id', $currentClass->id)
                ->where('assessment_type', '!=', AssessmentType::RECOVERY->value)
                ->whereNotNull('score')
                ->get();

            $bySubject  = $allGrades->groupBy('subject_id');
            $subjectAvgs = $bySubject->map(fn($g) => $g->avg('score'))->filter();
            $minApproval = GradeCalculationService::MIN_APPROVAL;

            $gradeStats = [
                'average'  => $subjectAvgs->isNotEmpty() ? round($subjectAvgs->avg(), 1) : null,
                'total'    => $bySubject->count(),
                'approved' => $subjectAvgs->filter(fn($a) => $a >= $minApproval)->count(),
                'recovery' => $subjectAvgs->filter(fn($a) => $a >= 4.0 && $a < $minApproval)->count(),
                'failed'   => $subjectAvgs->filter(fn($a) => $a < 4.0)->count(),
            ];

            // Aulas de hoje
            $todayLessons = Lesson::where('class_id', $currentClass->id)
                ->whereDate('date', today())
                ->with(['subject', 'teacher.user'])
                ->orderBy('start_time')
                ->get();

            // Próximas avaliações (7 dias)
            $upcomingAssessments = Assessment::where('class_id', $currentClass->id)
                ->where('scheduled_at', '>=', now()->startOfDay())
                ->where('scheduled_at', '<=', now()->addDays(7)->endOfDay())
                ->with(['subject'])
                ->orderBy('scheduled_at')
                ->limit(8)
                ->get();

            // Últimas notas lançadas
            $recentGrades = Grade::where('student_id', $student->id)
                ->where('class_id', $currentClass->id)
                ->whereNotNull('score')
                ->with(['subject'])
                ->latest('date_recorded')
                ->limit(6)
                ->get();

            return [
                'student'             => $student,
                'currentClass'        => $currentClass,
                'schoolYear'          => $currentClass->schoolYear,
                'attendance'          => $attendanceData,
                'grades'              => $gradeStats,
                'todayLessons'        => $todayLessons,
                'upcomingAssessments' => $upcomingAssessments,
                'recentGrades'        => $recentGrades,
            ];
        });
    }

    // ── Auxiliares ───────────────────────────────────────────────────────────

    private function emptyData(): array
    {
        return [
            'student'             => null,
            'currentClass'        => null,
            'schoolYear'          => null,
            'attendance'          => ['frequency' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'alert' => false],
            'grades'              => ['average' => null, 'total' => 0, 'approved' => 0, 'recovery' => 0, 'failed' => 0],
            'todayLessons'        => collect(),
            'upcomingAssessments' => collect(),
            'recentGrades'        => collect(),
        ];
    }
}
