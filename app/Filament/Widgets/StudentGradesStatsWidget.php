<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentGradesStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        $grades = Grade::where('student_id', $student->id)->get();

        // Calcula estatísticas
        $totalGrades = $grades->count();
        $averageScore = $grades->avg('score');
        $highestScore = $grades->max('score');
        $lowestScore = $grades->min('score');

        // Conta quantas disciplinas o aluno está cursando
        $subjects = $grades->pluck('subject_id')->unique()->count();

        // Verifica desempenho (considerando 7.0 como média mínima)
        $passingGrades = $grades->filter(fn($grade) => $grade->score >= 7.0)->count();
        $passingRate = $totalGrades > 0 ? round(($passingGrades / $totalGrades) * 100, 1) : 0;

        return [
            Stat::make('Média Geral', $averageScore ? number_format($averageScore, 2, ',', '.') : '-')
                ->description('Média de todas as avaliações')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($averageScore >= 7.0 ? 'success' : ($averageScore >= 5.0 ? 'warning' : 'danger'))
                ->chart($this->getScoresTrend()),

            Stat::make('Disciplinas', $subjects)
                ->description('Disciplinas cursando')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),

            Stat::make('Maior Nota', $highestScore ? number_format($highestScore, 2, ',', '.') : '-')
                ->description('Melhor desempenho')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Aproveitamento', $passingRate . '%')
                ->description('Notas acima de 7.0')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($passingRate >= 70 ? 'success' : ($passingRate >= 50 ? 'warning' : 'danger')),
        ];
    }

    protected function getScoresTrend(): array
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        // Pega as últimas 7 notas para mostrar tendência
        $recentGrades = Grade::where('student_id', $student->id)
            ->orderBy('date_recorded', 'desc')
            ->limit(7)
            ->pluck('score')
            ->reverse()
            ->toArray();

        return $recentGrades;
    }
}
