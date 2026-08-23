<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\PermissionAccess;
use Mockery;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        auth()->forgetUser();

        parent::tearDown();
    }

    public function test_administrator_cannot_use_student_portal_permissions(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('student')->andReturnFalse();
        $user->shouldNotReceive('roles');
        auth()->setUser($user);

        $this->assertFalse(PermissionAccess::can('student.dashboard.view'));
    }

    public function test_administrator_cannot_use_teacher_portal_permissions(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with('teacher')->andReturnFalse();
        $user->shouldNotReceive('roles');
        auth()->setUser($user);

        $this->assertFalse(PermissionAccess::can('teacher.classes.view'));
    }
}
