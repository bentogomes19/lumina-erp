<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPanelAccessTest extends TestCase {

    use RefreshDatabase;

    public function test_active_student_can_access_student_panel_but_not_the_administrative_panel(): void {
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('student');
        Student::factory()->create([
            'user_id' => $user->id,
            'status'  => 'active',
        ]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('aluno')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('lumina')));
    }

    public function test_student_with_suspended_academic_status_cannot_access_student_panel(): void {
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('student');
        Student::factory()->create([
            'user_id' => $user->id,
            'status'  => 'suspended',
        ]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('aluno')));
    }

    public function test_administrative_role_is_limited_to_the_administrative_panel(): void {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('lumina')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('aluno')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('professor')));
    }

    public function test_student_login_has_a_separate_route_from_the_administrative_login(): void {
        $this->assertSame('/aluno/login', parse_url(Filament::getPanel('aluno')->getLoginUrl(), PHP_URL_PATH));
        $this->assertSame('/lumina/login', parse_url(Filament::getPanel('lumina')->getLoginUrl(), PHP_URL_PATH));
        $this->assertSame('/professor/login', parse_url(Filament::getPanel('professor')->getLoginUrl(), PHP_URL_PATH));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.aluno.pages.dashboard-student'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('filament.lumina.pages.dashboard-student'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('filament.professor.pages.dashboard-teacher'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('filament.lumina.pages.dashboard-teacher'));

        $this->get('/aluno/login')
            ->assertOk()
            ->assertSee('lumina:theme:aluno')
            ->assertDontSee('lumina:theme:lumina')
            ->assertDontSee('lumina:theme:professor')
            ->assertSee('student-login-background.png')
            ->assertSee('auth-designer')
            ->assertDontSee('johnrivera7/filament-mia-theme/mia.css');

        $this->get('/lumina/login')
            ->assertOk()
            ->assertSee('lumina:theme:lumina')
            ->assertDontSee('lumina:theme:aluno')
            ->assertDontSee('lumina:theme:professor')
            ->assertSee('filament-qt5-theme-styles.css')
            ->assertDontSee('student-login-background.png');

        $this->get('/professor/login')
            ->assertOk()
            ->assertSee('lumina:theme:professor')
            ->assertDontSee('lumina:theme:lumina')
            ->assertDontSee('lumina:theme:aluno')
            ->assertDontSee('student-login-background.png');
    }

    public function test_administrator_and_student_can_keep_separate_panel_access_in_one_browser(): void {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'web']);
        $studentRole->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate([
            'name'       => 'student.dashboard.view',
            'guard_name' => 'web',
        ]));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('student');
        Student::factory()->create([
            'user_id' => $student->id,
            'status'  => 'active',
        ]);

        auth()->guard('web')->login($admin);
        $this->assertSame('student', Filament::getPanel('aluno')->getAuthGuard());
        $this->assertSame('web', Filament::getPanel('lumina')->getAuthGuard());
        $this->assertTrue($student->canAccessPanel(Filament::getPanel('aluno')));
        $this->assertTrue($student->can('student.dashboard.view'));

        $this->withSession(session()->all())
            ->get('/aluno/login')
            ->assertOk();

        auth()->guard('student')->login($student);

        $this->assertSame($admin->id, auth()->guard('web')->user()->id);
        $this->assertSame($student->id, auth()->guard('student')->user()->id);

        $this->actingAs($student, 'student');
        $this->assertSame($student->id, auth()->user()->id);
        $this->assertTrue($student->canAccessPanel(Filament::getPanel('aluno')));
        $this->assertTrue(\App\Support\PermissionCatalog::contains('student.dashboard.view'));
        $this->assertTrue(auth()->user()->hasRole('student'));
        $this->assertTrue(auth()->user()->can('student.dashboard.view'));
        $this->assertTrue(\App\Support\PermissionAccess::can('student.dashboard.view'));
        $this->assertTrue(\App\Filament\Pages\DashboardStudent::canAccess());
        $this->get('/aluno/dashboard-student')->assertOk();
        $this->assertSame($admin->id, auth()->guard('web')->user()->id);

        $this->actingAs($admin, 'web')
            ->get('/lumina/dashboard-admin')
            ->assertOk();
        $this->assertSame($student->id, auth()->guard('student')->user()->id);
    }
}
