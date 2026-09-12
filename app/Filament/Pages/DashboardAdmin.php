<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\AdminEnrollmentTrendChart;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\AdminRecentEnrollmentsTable;
use App\Filament\Widgets\AdminSchoolClassesTable;
use App\Models\SchoolYear;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;

class DashboardAdmin extends Dashboard {

    protected static ?string $navigationLabel                = 'Painel Administrativo';
    protected static ?string $title                          = 'Painel Administrativo';
    protected static string $routePath                       = '/dashboard-admin';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-house';
    protected static ?int $navigationSort                    = -1;

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    public function getWidgets(): array {
        return [
            AdminOverviewStats::class,
            AdminEnrollmentTrendChart::class,
            AdminSchoolClassesTable::class,
            AdminRecentEnrollmentsTable::class,
        ];
    }

    public function getColumns(): int | array {
        return [
            '@xl' => 2,
            '!@xl' => 1,
        ];
    }

    protected function getHeaderActions(): array {
        return [
            Action::make('configureSchoolYear')
                ->label('Configurar ano letivo')
                ->icon('fas-calendar-plus')
                ->url(SchoolYearResource::getUrl('create'))
                ->visible(!SchoolYear::current() && PermissionAccess::can('academic.school_years.create')),
            Action::make('newEnrollment')
                ->label('Nova matrícula')
                ->icon('fas-user-plus')
                ->url(EnrollmentResource::getUrl('create'))
                ->visible(PermissionAccess::can('academic.enrollments.create')),
            Action::make('newStudent')
                ->label('Novo aluno')
                ->icon('fas-user-plus')
                ->url(StudentResource::getUrl('create'))
                ->visible(PermissionAccess::can('academic.students.create')),
        ];
    }
}
