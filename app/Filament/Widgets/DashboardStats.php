<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Grades\GradeResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Grade;
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
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->url(StudentResource::getUrl('index')),

            Stat::make('Total de Professores', Teacher::count())
                ->description('Professores ativos')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->url(TeacherResource::getUrl('index')),

            Stat::make('Turmas', SchoolClass::count())
                ->description('Turmas registradas')
                ->icon('heroicon-o-rectangle-stack')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('index')),

            Stat::make('Matérias', Subject::count())
                ->description('Disciplinas cadastradas')
                ->icon('heroicon-o-book-open')
                ->color('primary')
                ->url(SubjectResource::getUrl('index')),

            Stat::make('Notas Lançadas', Grade::count())
                ->description('Registros de avaliações')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(GradeResource::getUrl('index')),

            Stat::make('Total de Usuários', User::count())
                ->description('Usuários cadastrados')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(UserResource::getUrl('index')),

            // Ações Rápidas
            Stat::make('Novo Aluno', '✓')
                ->description('Cadastrar aluno')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->url(StudentResource::getUrl('create')),

            Stat::make('Nova Turma', '✓')
                ->description('Criar turma')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->url(SchoolClassResource::getUrl('create')),

            Stat::make('Nova Matrícula', '✓')
                ->description('Registrar matrícula')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->url(EnrollmentResource::getUrl('create')),
        ];
    }
}
