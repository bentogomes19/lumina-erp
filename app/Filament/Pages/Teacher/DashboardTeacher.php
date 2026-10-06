<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Services\CurrentTeacherService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class DashboardTeacher extends Page {

    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel                = 'Portal do Professor';
    protected static ?string $title                          = 'Portal do Professor';
    protected static ?string $slug                           = 'dashboard-teacher';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-chart-line';
    protected static ?int $navigationSort                    = 1;
    protected static ?string $teacherPortalPermission        = 'teacher.dashboard.view';

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.dashboard-teacher';
    }

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
    public function getPageData(): array {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (!$teacher) {
            return $this->emptyData();
        }

        $assignments = $service->currentAssignments($teacher);
        $classIds    = $assignments->pluck('class_id')->filter()->unique()->values();
        $subjectIds  = $assignments->pluck('subject_id')->filter()->unique()->values();

        if ($assignments->isEmpty()) {
            return array_merge($this->emptyData(), [
                'teacher' => $teacher,
            ]);
        }

        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $lessonsThisWeek = $service->scopeAssignedPairs(Lesson::query(), $assignments)
            ->where('teacher_id', $teacher->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with(['schoolClass', 'subject'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $assessments = $service->scopeAssignedPairs(Assessment::query(), $assignments)
            ->where('teacher_id', $teacher->id)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->with(['schoolClass', 'subject'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $dueAssessments = $service->scopeAssignedPairs(Assessment::query(), $assignments)
            ->where('teacher_id', $teacher->id)
            ->where('status', 'open')
            ->where('scheduled_at', '<=', now())
            ->get();

        $pendingGrades = $dueAssessments->sum(fn (Assessment $assessment) =>
            Enrollment::query()
                ->where('class_id', $assessment->class_id)
                ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::SUSPENDED->value, EnrollmentStatus::LOCKED->value])
                ->whereDoesntHave('grades', fn ($query) => $query->where('assessment_id', $assessment->id))
                ->count()
        );

        $pendingAttendance = $service->scopeAssignedPairs(Lesson::query(), $assignments)
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', '<=', today())
            ->whereNotIn('status', [LessonStatus::CANCELLED->value, LessonStatus::RESCHEDULED->value])
            ->where(function ($query) {
                $query->where('attendance_taken', false)
                    ->orWhereNull('attendance_taken');
            })
            ->count();

        $recordedAttendance = $service->scopeAssignedPairs(Attendance::query(), $assignments)
            ->whereHas('lesson', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->whereDate('date', '>=', now()->subDays(7))
            ->count();

        return [
            'teacher'            => $teacher,
            'assignments'        => $assignments,
            'classes'            => $assignments->pluck('schoolClass')->filter()->unique('id')->values(),
            'subjects'           => $assignments->pluck('subject')->filter()->unique('id')->values(),
            'lessonsThisWeek'    => $lessonsThisWeek,
            'assessments'        => $assessments,
            'recordedAttendance' => $recordedAttendance,
            'cards'              => [
                [
                    'label'       => 'Minhas Turmas',
                    'value'       => $classIds->count(),
                    'icon'        => 'fas-users',
                    'color'       => 'var(--lumina-primary)',
                    'description' => 'turmas do ano ativo',
                ],
                [
                    'label'       => 'Disciplinas',
                    'value'       => $subjectIds->count(),
                    'icon'        => 'fas-book-open',
                    'color'       => '#06b6d4',
                    'description' => 'disciplinas atribuídas',
                ],
                [
                    'label'       => 'Aulas da Semana',
                    'value'       => $lessonsThisWeek->count(),
                    'icon'        => 'fas-calendar-week',
                    'color'       => '#0f766e',
                    'description' => 'aulas programadas',
                ],
                [
                    'label'       => 'Avaliações',
                    'value'       => $assessments->count(),
                    'icon'        => 'fas-clipboard-list',
                    'color'       => '#8b5cf6',
                    'description' => 'próximas avaliações',
                ],
                [
                    'label'       => 'Notas Pendentes',
                    'value'       => $pendingGrades,
                    'icon'        => 'fas-pen-to-square',
                    'color'       => '#eab308',
                    'description' => 'lançamentos incompletos',
                ],
                [
                    'label'       => 'Frequências Pendentes',
                    'value'       => $pendingAttendance,
                    'icon'        => 'fas-clipboard-check',
                    'color'       => '#ef4444',
                    'description' => 'chamadas a registrar',
                ],
                [
                    'label'       => 'Pendências',
                    'value'       => $pendingGrades + $pendingAttendance,
                    'icon'        => 'fas-triangle-exclamation',
                    'color'       => '#dc2626',
                    'description' => 'itens exigem atenção',
                ],
            ],
        ];
    }

    /**
     * Retorna a estrutura vazia de dados da página.
     *
     * @return array
     */
    private function emptyData(): array {
        return [
            'teacher'            => null,
            'assignments'        => collect(),
            'classes'            => collect(),
            'subjects'           => collect(),
            'lessonsThisWeek'    => collect(),
            'assessments'        => collect(),
            'recordedAttendance' => 0,
            'cards'              => [],
        ];
    }
}
