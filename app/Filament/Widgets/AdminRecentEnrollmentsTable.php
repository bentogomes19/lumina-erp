<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Support\InteractsWithAdminDashboardFilters;

class AdminRecentEnrollmentsTable extends TableWidget {

    protected string $view = 'filament.widgets.admin-table-widget';

    use InteractsWithPageFilters;
    use InteractsWithAdminDashboardFilters;

    protected static ?string $heading = 'Últimas matrículas';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user())
            && PermissionAccess::can('academic.enrollments.view_any');
    }

    public function getWidgetHeading(): string {
        return static::$heading;
    }

    public function table(Table $table): Table {
        $year = $this->dashboardSchoolYearId() ? SchoolYear::find($this->dashboardSchoolYearId()) : null;

        return $table
            ->query($this->dashboardEnrollmentQuery()
                ->with(['student', 'schoolClass'])
                ->when(!$this->dashboardSchoolYearId(), fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('registration_number')
                    ->label('Matrícula')
                    ->searchable()
                    ->url(fn (Enrollment $record): ?string => PermissionAccess::can('academic.enrollments.update')
                        ? EnrollmentResource::getUrl('edit', ['record' => $record])
                        : null),
                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->placeholder('Sem turma'),
                TextColumn::make('enrollment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (EnrollmentStatus|string|null $state): string => $state instanceof EnrollmentStatus
                        ? $state->label()
                        : (string) $state)
                    ->color(fn (Enrollment $record): string => $record->status?->color() ?? 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading($year ? 'Nenhuma matrícula registrada' : 'Ano letivo não configurado')
            ->emptyStateDescription($year
                ? 'As matrículas do período aparecerão aqui.'
                : 'Ative o ano letivo para acompanhar os registros.');
    }
}
