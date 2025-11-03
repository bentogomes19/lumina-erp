<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherStats extends BaseWidget
{
    protected ?string $heading = 'Visão Geral';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('teacher') ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $teacher = $user?->teacher;
        if (!$teacher) {
            return [
                Stat::make('Turmas', 0),
                Stat::make('Disciplinas', 0),
                Stat::make('Alunos', 0),
                Stat::make('Próxima avaliação', '—'),
            ];
        }

        // Turmas e disciplinas a partir de teacher_assignments
        $classIds   = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)->pluck('class_id')->unique();
        $subjectIds = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)->pluck('subject_id')->unique();

        // Total de alunos nas turmas do professor
        $studentCount = \App\Models\Enrollment::whereIn('class_id', $classIds)->distinct('student_id')->count('student_id');

        // Próxima avaliação
        $nextAssessment = \App\Models\Assessment::query()
            ->whereIn('class_id', $classIds)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('assessments','scheduled_at'),
                fn($q) => $q->where('scheduled_at', '>=', now())->orderBy('scheduled_at'))
            ->first();

        $nextLabel = $nextAssessment
            ? ($nextAssessment->title .' — '. optional($nextAssessment->scheduled_at)->format('d/m H:i'))
            : '—';

        return [
            Stat::make('Turmas', $classIds->count()),
            Stat::make('Disciplinas', $subjectIds->count()),
            Stat::make('Alunos', $studentCount)->description('Total nas suas turmas'),
            Stat::make('Próxima avaliação', $nextLabel),
        ];
    }
}
