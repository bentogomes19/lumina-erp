<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolYear;
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
        $from = $this->dashboardFilter('from_date');
        $until = $this->dashboardFilter('until_date');
        $status = $this->dashboardEnrollmentStatus();

        return Enrollment::query()
            ->when($this->dashboardSchoolYearId(), fn (Builder $query, int $yearId) => $query->where('school_year_id', $yearId))
            ->when($status && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($from, fn (Builder $query) => $query->whereDate('enrollment_date', '>=', $from))
            ->when($until, fn (Builder $query) => $query->whereDate('enrollment_date', '<=', $until));
    }
}
