<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentProfileWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $student = $user->student ?? null;

        if (!$student) {
            return [
                Stat::make('Perfil não vinculado', '—')
                    ->description('Este usuário não está vinculado a um aluno.')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('gray'),
            ];
        }

        // Ano letivo atual (ativo)
        $currentYear = SchoolYear::where('is_active', true)->first();

        // Matrícula do ano letivo atual
        $enrollment = null;

        if ($currentYear) {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->whereHas('schoolClass', fn ($q) => $q->where('school_year_id', $currentYear->id))
                ->with(['schoolClass.schoolYear', 'schoolClass.gradeLevel'])
                ->first();
        }

        // Fallback: se não achar matrícula no ano ativo, pega a mais recente
        if (! $enrollment) {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->with(['schoolClass.schoolYear', 'schoolClass.gradeLevel'])
                ->latest()
                ->first();
        }

        $schoolClass = $enrollment?->schoolClass;

        $anoLetivo = $schoolClass?->schoolYear?->year ?? 'Não definido';
        $turmaNome = $schoolClass?->name ?? 'Sem turma';
        $serie     = $schoolClass?->gradeLevel?->name ?? 'Sem série';

        // Notas: se quiser considerar só o ano atual, filtra pelo school_year_id
        $gradesQuery = Grade::query()
            ->where('student_id', $student->id);

        if ($currentYear) {
            $gradesQuery->whereHas('schoolClass', fn ($q) => $q->where('school_year_id', $currentYear->id));
        }

        $mediaNotas = (float) $gradesQuery->avg('score');
        $disciplinas = $gradesQuery->distinct('subject_id')->count('subject_id');

        return [
            Stat::make('Ano Letivo', $anoLetivo)
                ->description($currentYear ? 'Ano corrente da matrícula' : 'Nenhum ano letivo ativo')
                ->icon('heroicon-o-calendar')
                ->color($currentYear ? 'info' : 'gray'),

            Stat::make('Turma', $turmaNome)
                ->description("Série: {$serie}")
                ->icon('heroicon-o-user-group')
                ->color($schoolClass ? 'primary' : 'gray'),

            Stat::make('Média Geral', number_format($mediaNotas, 2, ',', '.'))
                ->description($mediaNotas > 0 ? 'Média das avaliações' : 'Sem notas ainda')
                ->icon('heroicon-o-academic-cap')
                ->color($mediaNotas > 0 ? 'success' : 'gray'),
        ];
    }
}
