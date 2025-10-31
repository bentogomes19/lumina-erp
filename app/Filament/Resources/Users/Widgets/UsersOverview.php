<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Usuários', User::count())
                ->color('primary')
                ->icon('heroicon-s-users'),

            Stat::make('Administradores', User::role('admin')->count())
                ->color('danger')
                ->icon('heroicon-s-users'),

            Stat::make('Professores', User::role('teacher')->count())
                ->color('warning')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Alunos', User::role('student')->count())
                ->color('info')
                ->icon('heroicon-o-user-group'),

        ];
    }
}
