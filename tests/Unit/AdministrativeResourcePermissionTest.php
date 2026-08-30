<?php

namespace Tests\Unit;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\GradeLevels\GradeLevelResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\TeacherAssignments\TeacherAssignmentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Student;
use App\Models\User;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdministrativeResourcePermissionTest extends TestCase {

    /**
     * Restaura o usuário autenticado após cada cenário.
     *
     * @return void
     */
    protected function tearDown(): void {
        auth()->forgetUser();

        parent::tearDown();
    }

    /**
     * Garante que a matriz canônica controle cada ação dos Resources administrativos.
     *
     * @param class-string $resource
     * @param string $action
     * @param string $permission
     *
     * @return void
     */
    #[DataProvider('resourceActions')]
    public function test_resource_action_uses_canonical_permission(
        string $resource,
        string $action,
        string $permission,
    ): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->once()->with($permission)->andReturnTrue();
        auth()->setUser($user);

        $allowed = in_array($action, ['canEdit', 'canDelete'], true)
            ? $resource::$action(null)
            : $resource::$action();

        $this->assertTrue($allowed);
    }

    /**
     * Garante que remover a permissão de listagem também oculte a navegação.
     *
     * @return void
     */
    public function test_resource_navigation_is_hidden_without_view_any_permission(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')
            ->once()
            ->with('academic.students.view_any')
            ->andReturnFalse();
        auth()->setUser($user);

        $this->assertFalse(StudentResource::shouldRegisterNavigation());
    }

    /**
     * Garante que ocultar a ação de edição também bloqueie o acesso direto ao registro.
     *
     * @return void
     */
    public function test_resource_rejects_direct_edit_without_permission(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')
            ->once()
            ->with('academic.students.update')
            ->andReturnFalse();
        auth()->setUser($user);

        $this->assertFalse(StudentResource::canEdit(new Student()));
    }

    /**
     * Retorna a matriz de Resource, ação e permissão canônica esperada.
     *
     * @return array<string, array{class-string, string, string}>
     */
    public static function resourceActions(): array {
        $resources = [
            'alunos'       => [StudentResource::class, 'academic.students'],
            'matrículas'   => [EnrollmentResource::class, 'academic.enrollments'],
            'turmas'       => [SchoolClassResource::class, 'academic.classes'],
            'disciplinas'  => [SubjectResource::class, 'academic.subjects'],
            'anos letivos' => [SchoolYearResource::class, 'academic.school_years'],
            'séries'       => [GradeLevelResource::class, 'academic.grade_levels'],
            'professores'  => [TeacherResource::class, 'admin.teachers'],
            'alocações'    => [TeacherAssignmentResource::class, 'admin.teachers.assignments'],
            'usuários'     => [UserResource::class, 'system.users'],
            'perfis'       => [RoleResource::class, 'system.roles'],
        ];
        $actions = [
            'listar'  => ['canViewAny', 'view_any'],
            'criar'   => ['canCreate', 'create'],
            'editar'  => ['canEdit', 'update'],
            'excluir' => ['canDelete', 'delete'],
        ];
        $matrix = [];

        foreach ($resources as $label => [$resource, $prefix]) {
            foreach ($actions as $actionLabel => [$method, $suffix]) {
                if ($resource === EnrollmentResource::class && $suffix === 'delete') {
                    $suffix = 'cancel';
                }

                $matrix["{$label} {$actionLabel}"] = [
                    $resource,
                    $method,
                    "{$prefix}.{$suffix}",
                ];
            }
        }

        return $matrix;
    }
}
