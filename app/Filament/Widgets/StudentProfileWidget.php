<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Services\GradeCalculationService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentProfileWidget extends StatsOverviewWidget {

    /**
     * Determina se o widget pode ser exibido ao usuário autenticado.
     *
     * @return bool
     */
    public static function canView(): bool {
        return \App\Support\PermissionAccess::can('student.profile.view');
    }

    /**
     * Retorna o ano letivo, a turma e a média geral do aluno autenticado.
     *
     * @return array
     */
    protected function getStats(): array {
        $user    = auth()->user();
        $student = $user->student ?? null;

        if (!$student) {
            return [
                Stat::make('Perfil não vinculado', '—')
                    ->description('Este usuário não está vinculado a um aluno.')
                    ->icon('fas-circle-exclamation')
                    ->color('gray'),
            ];
        }

        $schoolClass = $student->currentClass();

        $anoLetivo = $schoolClass?->schoolYear?->year ?? 'Não definido';
        $turmaNome = $schoolClass?->name ?? 'Sem turma';
        $serie     = $schoolClass?->gradeLevel?->name ?? 'Sem série';

        $grades = $schoolClass
            ? Grade::published()->where('student_id', $student->id)->where('class_id', $schoolClass->id)->get()
            : collect();
        $mediaNotas  = app(GradeCalculationService::class)->overallAverage($grades);
        $disciplinas = $grades->pluck('subject_id')->unique()->count();

        return [
            Stat::make('Ano Letivo', $anoLetivo)
                ->description($schoolClass ? 'Ano corrente da matrícula' : 'Nenhum ano letivo ativo')
                ->icon('fas-calendar')
                ->color($schoolClass ? 'info' : 'gray'),

            Stat::make('Turma', $turmaNome)
                ->description("Série: {$serie}")
                ->icon('fas-user-group')
                ->color($schoolClass ? 'primary' : 'gray'),

            Stat::make('Média Geral', $mediaNotas !== null ? number_format($mediaNotas, 2, ',', '.') : '-')
                ->description($mediaNotas !== null ? 'Média anual das disciplinas' : 'Sem notas ainda')
                ->icon('fas-graduation-cap')
                ->color($mediaNotas !== null ? 'success' : 'gray'),
        ];
    }
}
