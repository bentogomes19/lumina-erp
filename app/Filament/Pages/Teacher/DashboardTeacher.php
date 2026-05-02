<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Lesson;
use App\Services\CurrentTeacherService;
use Filament\Pages\Page;

class DashboardTeacher extends Page
{
    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel = 'Portal do Professor';
    protected static ?string $title = 'Portal do Professor';
    protected static ?string $slug = 'dashboard-teacher';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-chart-line';
    protected static ?int $navigationSort = 1;
    protected static ?string $teacherPortalPermission = 'teacher.dashboard.view';

    public function getView(): string
    {
        return 'filament.pages.teacher.dashboard-teacher';
    }

    public function getPageData(): array
    {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (! $teacher) {
            return $this->emptyData();
        }

        $assignments = $service->assignments($teacher);
        $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
        $subjectIds = $assignments->pluck('subject_id')->filter()->unique()->values();

        if ($assignments->isEmpty()) {
            return array_merge($this->emptyData(), [
                'teacher' => $teacher,
            ]);
        }

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $lessonsThisWeek = Lesson::query()
            ->where(function ($query) use ($teacher, $classIds, $subjectIds) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($classIds, $subjectIds) {
                        $nested->whereIn('class_id', $classIds)
                            ->whereIn('subject_id', $subjectIds);
                    });
            })
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with(['schoolClass', 'subject'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $assessments = Assessment::query()
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->with(['schoolClass', 'subject'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $pendingGrades = Grade::query()
            ->where(function ($query) use ($teacher, $classIds, $subjectIds) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($classIds, $subjectIds) {
                        $nested->whereIn('class_id', $classIds)
                            ->whereIn('subject_id', $subjectIds);
                    });
            })
            ->whereNull('score')
            ->count();

        $pendingAttendance = Lesson::query()
            ->where(function ($query) use ($teacher, $classIds, $subjectIds) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($classIds, $subjectIds) {
                        $nested->whereIn('class_id', $classIds)
                            ->whereIn('subject_id', $subjectIds);
                    });
            })
            ->whereDate('date', '<=', today())
            ->where(function ($query) {
                $query->where('attendance_taken', false)
                    ->orWhereNull('attendance_taken');
            })
            ->count();

        $recordedAttendance = Attendance::query()
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('date', '>=', now()->subDays(7))
            ->count();

        return [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'classes' => $assignments->pluck('schoolClass')->filter()->unique('id')->values(),
            'subjects' => $assignments->pluck('subject')->filter()->unique('id')->values(),
            'lessonsThisWeek' => $lessonsThisWeek,
            'assessments' => $assessments,
            'recordedAttendance' => $recordedAttendance,
            'cards' => [
                [
                    'label' => 'Minhas Turmas',
                    'value' => $classIds->count(),
                    'icon' => 'fas-users',
                    'color' => '#f59e0b',
                    'description' => 'turmas vinculadas',
                ],
                [
                    'label' => 'Disciplinas',
                    'value' => $subjectIds->count(),
                    'icon' => 'fas-book-open',
                    'color' => '#06b6d4',
                    'description' => 'disciplinas atribuídas',
                ],
                [
                    'label' => 'Aulas da Semana',
                    'value' => $lessonsThisWeek->count(),
                    'icon' => 'fas-calendar-week',
                    'color' => '#0f766e',
                    'description' => 'aulas programadas',
                ],
                [
                    'label' => 'Avaliações',
                    'value' => $assessments->count(),
                    'icon' => 'fas-clipboard-list',
                    'color' => '#8b5cf6',
                    'description' => 'próximas avaliações',
                ],
                [
                    'label' => 'Notas Pendentes',
                    'value' => $pendingGrades,
                    'icon' => 'fas-pen-to-square',
                    'color' => '#eab308',
                    'description' => 'lançamentos incompletos',
                ],
                [
                    'label' => 'Frequências Pendentes',
                    'value' => $pendingAttendance,
                    'icon' => 'fas-clipboard-check',
                    'color' => '#ef4444',
                    'description' => 'chamadas a registrar',
                ],
                [
                    'label' => 'Comunicados',
                    'value' => 0,
                    'icon' => 'fas-bullhorn',
                    'color' => '#0284c7',
                    'description' => 'não lidos',
                ],
                [
                    'label' => 'Pendências',
                    'value' => $pendingGrades + $pendingAttendance,
                    'icon' => 'fas-triangle-exclamation',
                    'color' => '#dc2626',
                    'description' => 'itens exigem atenção',
                ],
            ],
        ];
    }

    private function emptyData(): array
    {
        return [
            'teacher' => null,
            'assignments' => collect(),
            'classes' => collect(),
            'subjects' => collect(),
            'lessonsThisWeek' => collect(),
            'assessments' => collect(),
            'recordedAttendance' => 0,
            'cards' => [],
        ];
    }
}
