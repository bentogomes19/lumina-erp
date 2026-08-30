<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\EnrollmentPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CanonicalPolicyTest extends TestCase {

    /**
     * Garante que as Policies consultem a mesma permissão canônica dos Resources.
     *
     * @param object $policy
     * @param string $method
     * @param array<int, mixed> $arguments
     * @param string $permission
     *
     * @return void
     */
    #[DataProvider('policyActions')]
    public function test_policy_action_uses_canonical_permission(
        object $policy,
        string $method,
        array $arguments,
        string $permission,
    ): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->once()->with($permission)->andReturnTrue();

        $this->assertTrue($policy->{$method}($user, ...$arguments));
    }

    /**
     * Retorna ações representativas das Policies administrativas.
     *
     * @return array<string, array{object, string, array<int, mixed>, string}>
     */
    public static function policyActions(): array {
        return [
            'alunos listar'       => [new StudentPolicy(), 'viewAny', [], 'academic.students.view_any'],
            'alunos visualizar'   => [new StudentPolicy(), 'view', [new Student()], 'academic.students.view'],
            'matrículas criar'    => [new EnrollmentPolicy(), 'create', [], 'academic.enrollments.create'],
            'matrículas editar'   => [new EnrollmentPolicy(), 'update', [new Enrollment()], 'academic.enrollments.update'],
            'professores excluir' => [new TeacherPolicy(), 'delete', [new Teacher()], 'admin.teachers.delete'],
            'usuários visualizar' => [new UserPolicy(), 'view', [new User()], 'system.users.view'],
        ];
    }
}
