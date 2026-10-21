<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\Student;
use App\Services\GradeCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentGradesStatsWidget extends BaseWidget {

    /**
     * Determina se o widget pode ser exibido ao usuário autenticado.
     *
     * @return bool
     */
    public static function canView(): bool {
        return \App\Support\PermissionAccess::can('student.grades.view');
    }

    /**
     * Retorna a média, os extremos e o aproveitamento das notas do aluno.
     *
     * @return array
     */
    protected function getStats(): array {
        $user    = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        $currentClass = $student->currentClass();
        $grades = $currentClass
            ? Grade::published()->where('student_id', $student->id)->where('class_id', $currentClass->id)->get()
            : collect();

        /* Calcula estatísticas. */
        $totalGrades  = $grades->count();
        $averageScore = app(GradeCalculationService::class)->overallAverage($grades);
        $highestScore = $grades->max('score');
        $lowestScore  = $grades->min('score');

        /* Conta quantas disciplinas o aluno está cursando. */
        $subjects = $grades->pluck('subject_id')->unique()->count();

        $minimum = app(GradeCalculationService::class)->minimumApproval();
        $passingGrades = $grades->filter(fn ($grade) => $grade->score !== null && $grade->score >= $minimum)->count();
        $passingRate   = $totalGrades > 0 ? round(($passingGrades / $totalGrades) * 100, 1) : 0;

        return [
            Stat::make('Média Geral', $averageScore !== null ? number_format($averageScore, 2, ',', '.') : '-')
                ->description('Média anual das disciplinas')
                ->descriptionIcon('fas-chart-bar')
                ->color($averageScore === null ? 'gray' : ($averageScore >= $minimum ? 'success' : 'warning'))
                ->chart($this->getScoresTrend()),

            Stat::make('Disciplinas', $subjects)
                ->description('Disciplinas cursando')
                ->descriptionIcon('fas-book-open')
                ->color('info'),

            Stat::make('Maior Nota', $highestScore !== null ? number_format($highestScore, 2, ',', '.') : '-')
                ->description('Melhor desempenho')
                ->descriptionIcon('fas-arrow-trend-up')
                ->color($highestScore === null ? 'gray' : ($highestScore >= $minimum ? 'success' : 'warning')),

            Stat::make('Aproveitamento', $passingRate . '%')
                ->description('Notas na média (≥ '.number_format($minimum, 1, ',', '').')')
                ->descriptionIcon('fas-circle-check')
                ->color($passingRate >= 70 ? 'success' : ($passingRate >= 50 ? 'warning' : 'danger')),
        ];
    }

    /**
     * Retorna a evolução das notas no período analisado.
     *
     * @return array
     */
    protected function getScoresTrend(): array {
        $user    = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        /* Pega as últimas 7 notas para mostrar tendência. */
        $currentClass = $student->currentClass();
        if (!$currentClass) {
            return [];
        }

        $recentGrades = Grade::published()->where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->orderBy('date_recorded', 'desc')
            ->limit(7)
            ->pluck('score')
            ->reverse()
            ->toArray();

        return $recentGrades;
    }
}
