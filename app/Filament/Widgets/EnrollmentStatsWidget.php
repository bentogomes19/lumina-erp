<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Painel de contagem de matrículas por status para o ano letivo ativo.
 * Exibido no topo da listagem de matrículas.
 */
class EnrollmentStatsWidget extends StatsOverviewWidget
{
    // Atualiza ao navegar para a página (sem polling automático)
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Filtra pelo ano letivo ativo por padrão
        $activeYearId = SchoolYear::where('is_active', true)->value('id');

        $query = Enrollment::query();
        if ($activeYearId) {
            $query->where('school_year_id', $activeYearId);
        }

        $counts = $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $get = fn (EnrollmentStatus $s) => (int) ($counts[$s->value] ?? 0);

        $total = array_sum($counts);

        return [
            Stat::make('Total de Matrículas', $total)
                ->description($activeYearId ? 'Ano letivo atual' : 'Todos os anos')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Ativas', $get(EnrollmentStatus::ACTIVE))
                ->description('Matrículas ativas')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Trancadas', $get(EnrollmentStatus::LOCKED))
                ->description('Trancamentos vigentes')
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('Canceladas', $get(EnrollmentStatus::CANCELED))
                ->description('Canceladas no período')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Transferidas', $get(EnrollmentStatus::TRANSFERRED_INTERNAL) + $get(EnrollmentStatus::TRANSFERRED_EXTERNAL))
                ->description('Internas + Externas')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('info'),

            Stat::make('Concluídas', $get(EnrollmentStatus::COMPLETED))
                ->description('Encerradas com êxito')
                ->icon('heroicon-o-trophy')
                ->color('primary'),
        ];
    }
}
