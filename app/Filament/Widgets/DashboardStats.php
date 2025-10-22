<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Alunos', Student::count())
                ->description('Alunos cadastrados')
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Total de Professores', Teacher::count())
                ->description('Professores ativos')
                ->icon('heroicon-o-academic-cap')
                ->color('info'),

            Stat::make('Turmas', SchoolClass::count())
                ->description('Turmas registradas')
                ->icon('heroicon-o-rectangle-stack')
                ->color('warning'),

            Stat::make('Matérias', Subject::count())
                ->description('Disciplinas cadastradas')
                ->icon('heroicon-o-book-open')
                ->color('primary'),

            Stat::make('Notas Lançadas', Grade::count())
                ->description('Registros de avaliações')
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
        ];
    }
}
