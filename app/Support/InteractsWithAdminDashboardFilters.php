<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Models\SchoolYear;
use App\Modules\Reports\Application\DashboardEnrollmentQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * Compartilha o contexto dos filtros do dashboard entre os widgets.
 */
trait InteractsWithAdminDashboardFilters {

    protected function dashboardFilter(string $key, mixed $default = null): mixed {
        return $this->pageFilters[$key] ?? $default;
    }

    protected function dashboardSchoolYearId(): ?int {
        $selected = $this->dashboardFilter('school_year_id');

        return $selected ? (int) $selected : SchoolYear::current()?->id;
    }

    protected function dashboardEnrollmentStatus(): string {
        return (string) ($this->dashboardFilter('enrollment_status') ?: EnrollmentStatus::ACTIVE->value);
    }

    protected function dashboardEnrollmentQuery(): Builder {
        return app(DashboardEnrollmentQuery::class)->build(
            schoolYearId: $this->dashboardSchoolYearId(),
            status: $this->dashboardEnrollmentStatus(),
            from: $this->dashboardFilter('from_date'),
            until: $this->dashboardFilter('until_date'),
        );
    }
}
