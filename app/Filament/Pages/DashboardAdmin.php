<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\AdminEnrollmentTrendChart;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\AdminQuickAccess;
use App\Filament\Widgets\AdminRecentEnrollmentsTable;
use App\Filament\Widgets\AdminStrategicStats;
use App\Models\SchoolYear;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\EnrollmentStatus;

class DashboardAdmin extends Dashboard {

    use HasFiltersForm;

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
            AdminQuickAccess::class,
            AdminEnrollmentTrendChart::class,
            AdminOverviewStats::class,
            AdminStrategicStats::class,
            AdminRecentEnrollmentsTable::class,
        ];
    }

    public function getColumns(): int | array {
        return [
            '@xl' => 2,
            '!@xl' => 1,
        ];
    }

    public function filtersForm(Schema $schema): Schema {
        return $schema->components([
            Section::make('Filtros de análise')
                ->description('Ajuste o período para atualizar os indicadores, gráficos e listas do painel.')
                ->icon('fas-sliders')
                ->collapsible()
                ->persistCollapsed()
                ->schema([
                    Select::make('school_year_id')
                        ->label('Ano letivo')
                        ->options(SchoolYear::query()->orderByDesc('year')->pluck('year', 'id'))
                        ->default(SchoolYear::current()?->id)
                        ->selectablePlaceholder(false),
                    Select::make('enrollment_status')
                        ->label('Situação da matrícula')
                        ->options(['all' => 'Todas'] + EnrollmentStatus::options())
                        ->default(EnrollmentStatus::ACTIVE->value)
                        ->selectablePlaceholder(false),
                    DatePicker::make('from_date')
                        ->label('Matrículas a partir de')
                        ->placeholder('Sem limite inicial')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->locale('pt_BR')
                        ->firstDayOfWeek(1)
                        ->closeOnDateSelection()
                        ->maxDate(fn ($get) => $get('until_date')),
                    DatePicker::make('until_date')
                        ->label('Matrículas até')
                        ->placeholder('Sem limite final')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->locale('pt_BR')
                        ->firstDayOfWeek(1)
                        ->closeOnDateSelection()
                        ->minDate(fn ($get) => $get('from_date')),
                ])
                ->columns(['md' => 2, 'xl' => 4])
                ->columnSpanFull(),
        ]);
    }

    public function persistsFiltersInSession(): bool {
        return false;
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
