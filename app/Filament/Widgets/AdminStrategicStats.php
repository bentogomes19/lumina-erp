<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Models\SchoolClass;
use App\Support\AdministrativeDashboardAccess;
use App\Support\InteractsWithAdminDashboardFilters;
use App\Support\PermissionAccess;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStrategicStats extends StatsOverviewWidget {

    use InteractsWithPageFilters;
    use InteractsWithAdminDashboardFilters;

    protected ?string $heading = 'Visão estratégica';

    protected ?string $description = 'Indicadores para orientar capacidade, crescimento e operação.';

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user())
            && PermissionAccess::can('academic.enrollments.view_any');
    }

    protected function getStats(): array {
        $classes = $this->dashboardSchoolYearId()
            ? SchoolClass::query()
                ->where('school_year_id', $this->dashboardSchoolYearId())
                ->withCount(['enrollments as occupied_count' => fn ($query) => $query
                    ->when($this->dashboardEnrollmentStatus() !== 'all', fn ($q) => $q->where('status', $this->dashboardEnrollmentStatus()), fn ($q) => $q->whereIn('status', EnrollmentStatus::occupyingValues()))])
                ->get()
            : collect();

        $capacity = (int) $classes->sum(fn ($class) => (int) $class->capacity);
        $occupied = (int) $classes->sum('occupied_count');
        $occupancy = $capacity > 0 ? round(($occupied / $capacity) * 100) : 0;
        $withoutTeacher = $classes->filter(fn ($class) => ! $class->homeroom_teacher_id)->count();

        $enrollments = $this->dashboardEnrollmentQuery()->count();
        $active = $this->dashboardEnrollmentQuery()->where('status', EnrollmentStatus::ACTIVE->value)->count();

        return [
            Stat::make('Ocupação das vagas', "{$occupancy}%")
                ->description("{$occupied} de {$capacity} vagas utilizadas")
                ->icon('fas-chart-pie')
                ->color($occupancy >= 95 ? 'danger' : ($occupancy >= 80 ? 'warning' : 'success')),
            Stat::make('Conversão em ativas', $enrollments > 0 ? round(($active / $enrollments) * 100) . '%' : '0%')
                ->description("{$active} matrículas ativas no filtro")
                ->icon('fas-arrow-trend-up')
                ->color('primary'),
            Stat::make('Turmas sem regência', $withoutTeacher)
                ->description('Prioridade para alocação pedagógica')
                ->icon('fas-triangle-exclamation')
                ->color($withoutTeacher > 0 ? 'warning' : 'success'),
            Stat::make('Capacidade disponível', max(0, $capacity - $occupied))
                ->description('Vagas livres nas turmas abertas')
                ->icon('fas-chair')
                ->color('info'),
        ];
    }

}
