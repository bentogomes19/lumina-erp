<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
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

        $enrollment = Enrollment::where('student_id', $student->id)
            ->latest()
            ->with('schoolClass')
            ->first();

        $anoLetivo = $enrollment->schoolClass->year ?? 'Não definido';
        $turmaNome = $enrollment->schoolClass->name ?? 'Sem turma';
        $serie = $enrollment->schoolClass->grade_level ?? 'Sem série';

        $mediaNotas = Grade::where('student_id', $student->id)->avg('score') ?? 0;
        $disciplinas = Grade::where('student_id', $student->id)->distinct('subject_id')->count('subject_id');
        $turmas = Enrollment::where('student_id', $student->id)->count();

        return [
            Stat::make('Ano Letivo', $anoLetivo)
                ->description('Ano corrente da matrícula')
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Turma', $turmaNome)
                ->description("Série: {$serie}")
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Média Geral', number_format($mediaNotas, 2, ',', '.'))
                ->description($mediaNotas > 0 ? 'Média das avaliações' : 'Sem notas ainda')
                ->icon('heroicon-o-academic-cap')
                ->color($mediaNotas > 0 ? 'success' : 'gray'),
        ];
    }
}
