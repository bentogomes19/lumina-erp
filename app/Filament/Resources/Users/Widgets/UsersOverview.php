<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $ativos    = User::where('active', true)->count();
        $inativos  = User::where('active', false)->count();
        $bloqueados = User::whereNotNull('locked_at')->count();
        $aguardandoTroca = User::where('force_password_change', true)->count();

        return [
            Stat::make('Usuários Ativos', $ativos)
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make('Bloqueados', $bloqueados)
                ->color($bloqueados > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-lock-closed'),

            Stat::make('Aguardando troca de senha', $aguardandoTroca)
                ->color($aguardandoTroca > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-key'),

            Stat::make('TI / Admin', User::role(['ti', 'admin'])->count())
                ->color('danger')
                ->icon('heroicon-o-shield-check'),

            Stat::make('Secretaria / Financeiro',
                User::role(['secretaria', 'financeiro'])->count()
            )
                ->color('warning')
                ->icon('heroicon-o-building-office'),

            Stat::make('Professores', User::role('teacher')->count())
                ->color('primary')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Alunos', User::role('student')->count())
                ->color('info')
                ->icon('heroicon-o-user-group'),
        ];
    }
}
