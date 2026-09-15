<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\RelationManagers\SubjectsRelationManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InactiveTeacherAssignmentDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    public function test_class_subject_screen_displays_soft_deleted_teacher_as_inactive(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach (['academic.classes.view_any', 'academic.classes.view', 'academic.classes.update'] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $schoolClass = SchoolClass::factory()->create();
        $subject = Subject::factory()->create();
        $teacher = Teacher::factory()->create(['name' => 'Professora com histórico']);

        TeacherAssignment::create([
            'teacher_id' => $teacher->getKey(),
            'class_id' => $schoolClass->getKey(),
            'subject_id' => $subject->getKey(),
        ]);

        $teacher->delete();
        $this->actingAs($user);

        Livewire::test(SubjectsRelationManager::class, [
            'ownerRecord' => $schoolClass,
            'pageClass' => EditSchoolClass::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$subject])
            ->assertSee('Professora com histórico')
            ->assertSee('Inativo');
    }
}
