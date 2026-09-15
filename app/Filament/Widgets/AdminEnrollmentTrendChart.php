<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Support\InteractsWithAdminDashboardFilters;

class AdminEnrollmentTrendChart extends ChartWidget {

    use InteractsWithPageFilters;
    use InteractsWithAdminDashboardFilters;

    protected string $view = 'filament.widgets.admin-chart-widget';

    protected ?string $maxHeight = '280px';

    protected ?string $heading = 'Matrículas registradas por mês';

    protected ?string $description = 'Entradas registradas no período filtrado.';

    protected int | string | array $columnSpan = 'full';

    protected ?string $emptyStateHeading = 'Sem dados de matrícula';

    protected ?string $emptyStateDescription = 'Ative um ano letivo e registre matrículas para acompanhar a evolução mensal.';

    protected bool $isCollapsible = true;

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user())
            && PermissionAccess::can('academic.enrollments.view_any');
    }

    protected function getType(): string {
        return 'bar';
    }

    protected function getData(): array {
        if (!$this->dashboardSchoolYearId()) {
            return [];
        }

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', enrollment_date) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM enrollment_date) AS INTEGER)',
            default => 'MONTH(enrollment_date)',
        };

        $totals = $this->dashboardEnrollmentQuery()
            ->whereNotNull('enrollment_date')
            ->selectRaw("{$monthExpression} as enrollment_month, COUNT(*) as total")
            ->groupBy('enrollment_month')
            ->pluck('total', 'enrollment_month');

        $months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        return [
            'datasets' => [[
                'label' => 'Matrículas',
                'data' => collect(range(1, 12))
                    ->map(fn (int $month): int => (int) ($totals[$month] ?? 0))
                    ->all(),
                'backgroundColor' => 'rgba(61, 90, 128, 0.75)',
                'borderColor' => 'rgb(61, 90, 128)',
                'borderWidth' => 1,
            ]],
            'labels' => $months,
        ];
    }
}
