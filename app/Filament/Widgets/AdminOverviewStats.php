<?php

namespace App\Filament\Widgets;

use App\Enums\ClassStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStats extends StatsOverviewWidget {

    protected ?string $heading = 'Visão geral da escola';

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    protected function getDescription(): ?string {
        $year = SchoolYear::current();

        return $year
            ? "Indicadores do ano letivo {$year->year}."
            : 'Nenhum ano letivo está ativo. Os indicadores acadêmicos aparecem zerados até a ativação do período.';
    }

    protected function getStats(): array {
        $year = SchoolYear::current();
        $stats = [];

        $yearStat = Stat::make('Ano letivo ativo', $year?->year ?? 'Não configurado')
            ->description($year ? 'Período acadêmico em acompanhamento' : 'Configure o período da escola')
            ->icon('fas-calendar')
            ->color($year ? 'success' : 'warning');

        if (PermissionAccess::can('academic.school_years.view_any')) {
            $yearStat->url(SchoolYearResource::getUrl('index'));
        }

        $stats[] = $yearStat;

        if (PermissionAccess::can('academic.enrollments.view_any')) {
            $enrollments = $year
                ? Enrollment::query()
                    ->where('school_year_id', $year->id)
                    ->where('status', EnrollmentStatus::ACTIVE->value)
                    ->count()
                : 0;

            $stats[] = Stat::make('Matrículas ativas', $enrollments)
                ->description($year ? 'Vínculos ativos no ano letivo' : 'Sem ano letivo ativo')
                ->icon('fas-clipboard-check')
                ->color('primary')
                ->url(EnrollmentResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.students.view_any')) {
            $students = $year
                ? Student::query()
                    ->where('status', StudentStatus::ACTIVE->value)
                    ->whereHas('enrollments', fn ($query) => $query
                        ->where('school_year_id', $year->id)
                        ->where('status', EnrollmentStatus::ACTIVE->value))
                    ->count()
                : 0;

            $stats[] = Stat::make('Alunos ativos', $students)
                ->description($year ? 'Com matrícula ativa neste ano' : 'Sem ano letivo ativo')
                ->icon('fas-user-group')
                ->color('info')
                ->url(StudentResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.classes.view_any')) {
            $classes = $year
                ? SchoolClass::query()
                    ->where('school_year_id', $year->id)
                    ->where('status', ClassStatus::OPEN->value)
                    ->count()
                : 0;

            $stats[] = Stat::make('Turmas abertas', $classes)
                ->description($year ? 'Turmas abertas no período' : 'Sem ano letivo ativo')
                ->icon('fas-chalkboard')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('index'));
        }

        if (PermissionAccess::can('admin.teachers.view_any')) {
            $teachers = $year
                ? Teacher::query()
                    ->where('status', TeacherStatus::ACTIVE->value)
                    ->whereHas('teacherAssignments.schoolClass', fn ($query) => $query
                        ->where('school_year_id', $year->id)
                        ->where('status', ClassStatus::OPEN->value))
                    ->count()
                : 0;

            $stats[] = Stat::make('Professores em atividade', $teachers)
                ->description($year ? 'Com atribuições em turmas abertas' : 'Sem ano letivo ativo')
                ->icon('fas-person-chalkboard')
                ->color('success')
                ->url(TeacherResource::getUrl('index'));
        }

        return $stats;
    }
}
