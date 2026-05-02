<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Alunos', Student::count())
                ->description('Alunos cadastrados')
                ->icon('fas-user-group')
                ->color('success')
                ->url(StudentResource::getUrl('index')),

            Stat::make('Total de Professores', Teacher::count())
                ->description('Professores ativos')
                ->icon('fas-graduation-cap')
                ->color('info')
                ->url(TeacherResource::getUrl('index')),

            Stat::make('Turmas', SchoolClass::count())
                ->description('Turmas registradas')
                ->icon('fas-layer-group')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('index')),

            Stat::make('Matérias', Subject::count())
                ->description('Disciplinas cadastradas')
                ->icon('fas-book-open')
                ->color('primary')
                ->url(SubjectResource::getUrl('index')),

            Stat::make('Total de Usuários', User::count())
                ->description('Usuários cadastrados')
                ->icon('fas-users')
                ->color('primary')
                ->url(UserResource::getUrl('index')),

            // Ações Rápidas
            Stat::make('Novo Aluno', '✓')
                ->description('Cadastrar aluno')
                ->icon('fas-user-plus')
                ->color('success')
                ->url(StudentResource::getUrl('create')),

            Stat::make('Nova Turma', '✓')
                ->description('Criar turma')
                ->icon('fas-circle-plus')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('create')),

            Stat::make('Nova Matrícula', '✓')
                ->description('Registrar matrícula')
                ->icon('fas-clipboard-list')
                ->color('info')
                ->url(EnrollmentResource::getUrl('create')),
        ];
    }
}
