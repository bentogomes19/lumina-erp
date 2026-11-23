<?php

namespace Tests\Feature\Teachers;

use App\Filament\Pages\Teacher\DashboardTeacher;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_exposes_uploaded_photo_to_filament_menu(): void
    {
        Storage::fake('public');

        $withPhoto = User::factory()->create(['avatar' => 'avatars/foto.png']);
        $withoutPhoto = User::factory()->create(['avatar' => null]);

        $this->assertStringEndsWith('/storage/avatars/foto.png', $withPhoto->getFilamentAvatarUrl());
        $this->assertNull($withoutPhoto->getFilamentAvatarUrl());
    }

    public function test_teacher_dashboard_shows_the_uploaded_photo(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('professor'));

        $teacher = Teacher::factory()->create(['name' => 'Mariana Costa']);
        $teacher->user->update(['avatar' => 'avatars/mariana.png']);
        $this->login($teacher);

        Livewire::test(DashboardTeacher::class)
            ->assertSeeHtml('src="'.Storage::disk('public')->url('avatars/mariana.png').'"')
            ->assertSeeHtml('alt="Foto de Mariana Costa"');
    }

    public function test_teacher_dashboard_keeps_initials_without_photo(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('professor'));

        $teacher = Teacher::factory()->create(['name' => 'Mariana Costa']);
        $teacher->user->update(['avatar' => null]);
        $this->login($teacher);

        Livewire::test(DashboardTeacher::class)
            ->assertSee('MC')
            ->assertDontSeeHtml('alt="Foto de Mariana Costa"');
    }

    private function login(Teacher $teacher): void
    {
        $user = $teacher->user;
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('teacher.dashboard.view', 'web'));
        $this->actingAs($user, 'teacher');

        /* O card com a foto só aparece para professor com turma atribuída. */
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = Subject::factory()->create(['code' => 'CIE-AV', 'normalized_code' => 'cie-av', 'name' => 'Ciências']);
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
    }
}
