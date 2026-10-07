<?php

namespace Tests\Feature\Students;

use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalThemeTest extends TestCase {
    use RefreshDatabase;

    public function test_student_modules_render_with_the_administrative_theme(): void {
        $user = User::factory()->create(['active' => true, 'force_password_change' => false]);
        $user->assignRole(Role::findOrCreate('student', 'web'));
        foreach (['dashboard', 'grades', 'attendance', 'subjects', 'calendar'] as $module) {
            $user->givePermissionTo(Permission::findOrCreate("student.$module.view", 'web'));
        }

        $student = Student::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $year = SchoolYear::factory()->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $year->id,
        ]);

        $this->assertSame(
            Filament::getPanel('lumina')->getColors()['primary'],
            Filament::getPanel('aluno')->getColors()['primary'],
        );

        $this->actingAs($user, 'student');
        foreach (['dashboard-student', 'my-grades', 'student-attendance', 'my-subjects', 'academic-calendar'] as $page) {
            $this->get("/aluno/$page")
                ->assertOk()
                ->assertSee('lumina:theme:aluno')
                ->assertSee('filament-qt5-theme-styles.css');
        }
    }
}
