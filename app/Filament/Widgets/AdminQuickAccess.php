<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Widgets\Widget;

class AdminQuickAccess extends Widget {

    protected string $view = 'filament.widgets.admin-quick-access';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    protected function getViewData(): array {
        return [
            'actions' => collect([
                ['label' => 'Nova matrícula', 'description' => 'Inicie um novo vínculo', 'icon' => 'fas-user-plus', 'color' => 'primary', 'permission' => 'academic.enrollments.create', 'url' => EnrollmentResource::getUrl('create')],
                ['label' => 'Novo aluno', 'description' => 'Cadastre dados do estudante', 'icon' => 'fas-user-graduate', 'color' => 'info', 'permission' => 'academic.students.create', 'url' => StudentResource::getUrl('create')],
                ['label' => 'Nova turma', 'description' => 'Organize uma classe', 'icon' => 'fas-chalkboard', 'color' => 'warning', 'permission' => 'academic.classes.create', 'url' => SchoolClassResource::getUrl('create')],
                ['label' => 'Novo professor', 'description' => 'Inclua um profissional', 'icon' => 'fas-person-chalkboard', 'color' => 'success', 'permission' => 'admin.teachers.create', 'url' => TeacherResource::getUrl('create')],
            ])->filter(fn (array $action): bool => PermissionAccess::can($action['permission']))->values()->all(),
        ];
    }
}
