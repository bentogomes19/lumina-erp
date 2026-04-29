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
                ->icon('fas-users'),

            Stat::make('Bloqueados', $bloqueados)
                ->color($bloqueados > 0 ? 'danger' : 'gray')
                ->icon('fas-lock'),

            Stat::make('Aguardando troca de senha', $aguardandoTroca)
                ->color($aguardandoTroca > 0 ? 'warning' : 'gray')
                ->icon('fas-key'),

            Stat::make('TI / Admin', User::role(['ti', 'admin'])->count())
                ->color('danger')
                ->icon('fas-shield-halved'),

            Stat::make('Secretaria / Financeiro',
                User::role(['secretaria', 'financeiro'])->count()
            )
                ->color('warning')
                ->icon('fas-building'),

            Stat::make('Professores', User::role('teacher')->count())
                ->color('primary')
                ->icon('fas-graduation-cap'),

            Stat::make('Alunos', User::role('student')->count())
                ->color('info')
                ->icon('fas-user-group'),
        ];
    }
}
