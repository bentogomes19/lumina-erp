<?php

namespace App\Filament\Widgets;

use App\Enums\ClassStatus;
use App\Enums\ClassShift;
use App\Enums\EnrollmentStatus;
use App\Enums\TeacherStatus;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AdminSchoolClassesTable extends TableWidget {

    protected static ?string $heading = 'Turmas do ano letivo';

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user())
            && PermissionAccess::can('academic.classes.view_any');
    }

    public function table(Table $table): Table {
        $year = SchoolYear::current();

        return $table
            ->query(SchoolClass::query()
                ->with('gradeLevel')
                ->withCount([
                    'enrollments as occupied_enrollments_count' => fn (Builder $query) => $query
                        ->whereIn('status', EnrollmentStatus::occupyingValues()),
                    'teacherAssignments as active_teachers_count' => fn (Builder $query) => $query
                        ->whereHas('teacher', fn (Builder $teacherQuery) => $teacherQuery
                            ->where('status', TeacherStatus::ACTIVE->value)),
                ])
                ->when($year, fn (Builder $query) => $query->where('school_year_id', $year->id), fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->where('status', ClassStatus::OPEN->value)
                ->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Turma')
                    ->description(fn (SchoolClass $record): ?string => $record->gradeLevel?->name)
                    ->searchable()
                    ->url(fn (SchoolClass $record): ?string => PermissionAccess::can('academic.classes.update')
                        ? SchoolClassResource::getUrl('edit', ['record' => $record])
                        : null),
                TextColumn::make('shift')
                    ->label('Turno')
                    ->formatStateUsing(fn (ClassShift|string|null $state): string => $state instanceof ClassShift
                        ? $state->label()
                        : (string) $state)
                    ->badge(),
                TextColumn::make('occupied_enrollments_count')
                    ->label('Ocupação')
                    ->formatStateUsing(fn (int $state, SchoolClass $record): string => $record->capacity
                        ? "{$state} / {$record->capacity}"
                        : "{$state} ocupadas")
                    ->alignCenter(),
                TextColumn::make('active_teachers_count')
                    ->label('Professores')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : 'Sem atribuição'),
            ])
            ->defaultSort('name')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading($year ? 'Nenhuma turma aberta neste ano' : 'Ano letivo não configurado')
            ->emptyStateDescription($year
                ? 'As turmas abertas do período aparecerão aqui.'
                : 'Ative o ano letivo para acompanhar as turmas em operação.');
    }
}
