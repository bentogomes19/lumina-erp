<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentGradesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (!$user || !$user->student) {
            return [];
        }

        // Busca todas as notas do aluno
        $grades = Grade::query()
            ->where('student_id', $user->student->id)
            ->get();

        if ($grades->isEmpty()) {
            return [
                Stat::make('Notas Registradas', '0')
                    ->description('Nenhuma nota registrada ainda')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray'),
            ];
        }

        // Calcula estatísticas
        $totalGrades = $grades->count();
        $averageGrade = $grades->avg('score');
        $highestGrade = $grades->max('score');
        $lowestGrade = $grades->min('score');
        $disciplines = $grades->groupBy('subject_id')->count();

        return [
            Stat::make('Média Geral', number_format($averageGrade, 2, ',', '.'))
                ->description('De todas as avaliações')
                ->icon('heroicon-o-star')
                ->color($averageGrade >= 7 ? 'success' : ($averageGrade >= 5 ? 'warning' : 'danger')),

            Stat::make('Avaliações', $totalGrades)
                ->description('Notas registradas')
                ->icon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Disciplinas', $disciplines)
                ->description('Com notas registradas')
                ->icon('heroicon-o-book-open')
                ->color('primary'),

            Stat::make('Maior Nota', number_format($highestGrade, 2, ',', '.'))
                ->description('Melhor desempenho')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Menor Nota', number_format($lowestGrade, 2, ',', '.'))
                ->description('Desempenho mais baixo')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
