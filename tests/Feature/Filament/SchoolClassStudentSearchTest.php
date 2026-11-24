<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\RelationManagers\StudentsRelationManager;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolClassStudentSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    public function test_class_students_can_be_searched_by_name_and_registration_number(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach (['academic.classes.view_any', 'academic.classes.view', 'academic.classes.update', 'academic.students.view_any', 'academic.students.view'] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $schoolClass = SchoolClass::factory()->create();
        $miguel = Student::factory()->create(['name' => 'Miguel Andrade', 'registration_number' => 'ALU-2021-901']);
        $laura = Student::factory()->create(['name' => 'Laura Souza', 'registration_number' => 'ALU-2021-902']);

        foreach ([$miguel, $laura] as $student) {
            Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $schoolClass->id]);
        }

        $this->actingAs($user);

        $component = Livewire::test(StudentsRelationManager::class, [
            'ownerRecord' => $schoolClass,
            'pageClass' => EditSchoolClass::class,
        ]);

        $component
            ->searchTable('Miguel')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$miguel])
            ->assertCanNotSeeTableRecords([$laura]);

        $component
            ->searchTable('ALU-2021-902')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$laura])
            ->assertCanNotSeeTableRecords([$miguel]);
    }
}
