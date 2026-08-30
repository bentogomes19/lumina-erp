<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\PermissionAccess;
use Mockery;
use Tests\TestCase;

class PermissionAccessTest extends TestCase {

    /**
     * Restaura o ambiente após a execução do teste.
     *
     * @return void
     */
    protected function tearDown(): void {
        auth()->forgetUser();

        parent::tearDown();
    }

    /**
     * Verifica se um administrador não herda permissões exclusivas do portal do aluno.
     *
     * @return void
     */
    public function test_administrator_cannot_use_student_portal_permissions(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('student')->andReturnFalse();
        $user->shouldNotReceive('roles');
        auth()->setUser($user);

        $this->assertFalse(PermissionAccess::can('student.dashboard.view'));
    }

    /**
     * Verifica se um administrador não herda permissões exclusivas do portal do professor.
     *
     * @return void
     */
    public function test_administrator_cannot_use_teacher_portal_permissions(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('teacher')->andReturnFalse();
        $user->shouldNotReceive('roles');
        auth()->setUser($user);

        $this->assertFalse(PermissionAccess::can('teacher.classes.view'));
    }

    /**
     * Verifica se o papel docente não substitui uma permissão removida na matriz.
     *
     * @return void
     */
    public function test_teacher_role_does_not_bypass_missing_catalog_permission(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('teacher')->andReturnTrue();
        $user->shouldReceive('can')->once()->with('teacher.classes.view')->andReturnFalse();
        auth()->setUser($user);

        $this->assertFalse(PermissionAccess::can('teacher.classes.view'));
    }

    /**
     * Verifica se o portal libera uma permissão canônica concedida ao perfil correto.
     *
     * @return void
     */
    public function test_student_portal_accepts_explicit_catalog_permission(): void {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('student')->andReturnTrue();
        $user->shouldReceive('can')->once()->with('student.grades.view')->andReturnTrue();
        auth()->setUser($user);

        $this->assertTrue(PermissionAccess::can('student.grades.view'));
    }
}
