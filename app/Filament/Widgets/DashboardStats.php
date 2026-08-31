<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\AdministrativeDashboardAccess;
use App\Support\PermissionAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget {

    /**
     * Determina se o widget pode ser exibido para o usuário atual.
     *
     * @return bool
     */
    public static function canView(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    /**
     * Retorna os totais acadêmicos e os atalhos exibidos no painel administrativo.
     *
     * @return array
     */
    protected function getStats(): array {
        $stats = [];

        if (PermissionAccess::can('academic.students.view_any')) {
            $stats[] = Stat::make('Total de Alunos', Student::count())
                ->description('Alunos cadastrados')
                ->icon('fas-user-group')
                ->color('success')
                ->url(StudentResource::getUrl('index'));
        }

        if (PermissionAccess::can('admin.teachers.view_any')) {
            $stats[] = Stat::make('Total de Professores', Teacher::count())
                ->description('Professores ativos')
                ->icon('fas-graduation-cap')
                ->color('info')
                ->url(TeacherResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.enrollments.view_any')) {
            $stats[] = Stat::make('Matrículas', Enrollment::count())
                ->description('Matrículas registradas')
                ->icon('fas-clipboard-list')
                ->color('info')
                ->url(EnrollmentResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.classes.view_any')) {
            $stats[] = Stat::make('Turmas', SchoolClass::count())
                ->description('Turmas registradas')
                ->icon('fas-layer-group')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.subjects.view_any')) {
            $stats[] = Stat::make('Matérias', Subject::count())
                ->description('Disciplinas cadastradas')
                ->icon('fas-book-open')
                ->color('primary')
                ->url(SubjectResource::getUrl('index'));
        }

        if (PermissionAccess::can('system.users.view_any')) {
            $stats[] = Stat::make('Total de Usuários', User::count())
                ->description('Usuários cadastrados')
                ->icon('fas-users')
                ->color('primary')
                ->url(UserResource::getUrl('index'));
        }

        if (PermissionAccess::can('academic.students.create')) {
            $stats[] = Stat::make('Novo Aluno', 'OK')
                ->description('Cadastrar aluno')
                ->icon('fas-user-plus')
                ->color('success')
                ->url(StudentResource::getUrl('create'));
        }

        if (PermissionAccess::can('academic.classes.create')) {
            $stats[] = Stat::make('Nova Turma', 'OK')
                ->description('Criar turma')
                ->icon('fas-circle-plus')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('create'));
        }

        if (PermissionAccess::can('academic.enrollments.create')) {
            $stats[] = Stat::make('Nova Matrícula', 'OK')
                ->description('Registrar matrícula')
                ->icon('fas-clipboard-list')
                ->color('info')
                ->url(EnrollmentResource::getUrl('create'));
        }

        if ($stats === []) {
            $stats[] = Stat::make('Acesso em configuração', '-')
                ->description('Nenhum indicador liberado para este perfil')
                ->icon('fas-circle-info')
                ->color('gray');
        }

        return $stats;
    }
}
