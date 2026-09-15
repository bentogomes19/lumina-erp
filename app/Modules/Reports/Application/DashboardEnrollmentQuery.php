<?php

namespace App\Modules\Reports\Application;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta reutilizável de matrículas para indicadores administrativos.
 *
 * O objeto conhece apenas os filtros de leitura do dashboard. Regras de
 * autorização permanecem nas páginas e widgets que consomem o resultado.
 */
final class DashboardEnrollmentQuery {

    /**
     * @param int|null $schoolYearId
     * @param string $status
     * @param string|null $from
     * @param string|null $until
     *
     * @return Builder<Enrollment>
     */
    public function build(
        ?int $schoolYearId,
        string $status,
        ?string $from = null,
        ?string $until = null,
    ): Builder {
        return Enrollment::query()
            ->when($schoolYearId, fn (Builder $query, int $yearId) => $query->where('school_year_id', $yearId))
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($from, fn (Builder $query) => $query->whereDate('enrollment_date', '>=', $from))
            ->when($until, fn (Builder $query) => $query->whereDate('enrollment_date', '<=', $until));
    }
}
