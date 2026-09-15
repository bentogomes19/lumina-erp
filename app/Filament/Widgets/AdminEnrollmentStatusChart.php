<?php

namespace App\Filament\Widgets;

use App\Support\AdministrativeDashboardAccess;
use App\Support\InteractsWithAdminDashboardFilters;
use App\Support\PermissionAccess;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AdminEnrollmentStatusChart extends ChartWidget {

    use InteractsWithPageFilters;
    use InteractsWithAdminDashboardFilters;

    protected ?string $heading = 'Distribuição das matrículas';

    protected ?string $description = 'Situação dos vínculos no contexto filtrado.';

    protected bool $isCollapsible = true;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user())
            && PermissionAccess::can('academic.enrollments.view_any');
    }

    protected function getType(): string {
        return 'doughnut';
    }

    protected function getData(): array {
        $counts = $this->dashboardEnrollmentQuery()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = ['#16a34a', '#f59e0b', '#64748b', '#0ea5e9', '#a855f7', '#dc2626', '#2563eb'];

        foreach (\App\Enums\EnrollmentStatus::cases() as $index => $status) {
            $total = (int) ($counts[$status->value] ?? 0);
            if ($total === 0) {
                continue;
            }
            $labels[] = $status->label();
            $data[] = $total;
        }

        return [
            'datasets' => [[
                'label' => 'Matrículas',
                'data' => $data,
                'backgroundColor' => array_slice($colors, 0, count($data)),
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }
}
