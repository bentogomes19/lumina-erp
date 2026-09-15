<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Models\Attendance;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Actions\ForceDeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeletionGuardForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    #[DataProvider('resources')]
    public function test_linked_record_cannot_be_force_deleted(
        string $type,
        string $page,
        string $permissionPrefix,
        string $blockingLink,
    ): void {
        $user = $this->authorizedUser($permissionPrefix);
        $record = $this->trashedRecord($type);
        $this->addBlockingLink($type, $record);
        $this->actingAs($user);

        Livewire::test($page, ['record' => $record->getKey()])
            ->assertSuccessful()
            ->callAction(ForceDeleteAction::class)
            ->assertNotified(
                Notification::make()
                    ->title('Exclusão definitiva bloqueada')
                    ->body("- {$blockingLink}")
                    ->danger(),
            );

        $this->assertNotNull($record->newQueryWithoutScopes()->find($record->getKey()));
    }

    #[DataProvider('resources')]
    public function test_unlinked_record_can_be_force_deleted(
        string $type,
        string $page,
        string $permissionPrefix,
    ): void {
        $user = $this->authorizedUser($permissionPrefix);
        $record = $this->trashedRecord($type);
        $this->actingAs($user);

        Livewire::test($page, ['record' => $record->getKey()])
            ->assertSuccessful()
            ->callAction(ForceDeleteAction::class);

        $this->assertNull($record->newQueryWithoutScopes()->find($record->getKey()));
    }

    /**
     * @return array<string, array{string, class-string, string, string}>
     */
    public static function resources(): array
    {
        return [
            'professor' => ['teacher', EditTeacher::class, 'admin.teachers', '1 atribuição'],
            'aluno' => ['student', EditStudent::class, 'academic.students', '1 registro de frequência'],
            'disciplina' => ['subject', EditSubject::class, 'academic.subjects', '1 nível/série'],
            'turma' => ['class', EditSchoolClass::class, 'academic.classes', '1 disciplina'],
        ];
    }

    private function authorizedUser(string $permissionPrefix): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach (["{$permissionPrefix}.view_any", "{$permissionPrefix}.update", "{$permissionPrefix}.delete"] as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function trashedRecord(string $type): Model
    {
        $record = match ($type) {
            'teacher' => Teacher::factory()->create(),
            'student' => Student::factory()->create(),
            'subject' => Subject::factory()->create(),
            'class' => SchoolClass::factory()->create(),
        };
        $record->delete();

        return $record;
    }

    private function addBlockingLink(string $type, Model $record): void
    {
        match ($type) {
            'teacher' => $this->addTeacherAssignment($record),
            'student' => $this->addAttendance($record),
            'subject' => $this->addGradeLevel($record),
            'class' => $this->addSubject($record),
        };
    }

    private function addTeacherAssignment(Teacher $teacher): void
    {
        TeacherAssignment::create([
            'teacher_id' => $teacher->getKey(),
            'class_id' => SchoolClass::factory()->create()->getKey(),
            'subject_id' => Subject::factory()->create()->getKey(),
        ]);
    }

    private function addAttendance(Student $student): void
    {
        Attendance::create([
            'student_id' => $student->getKey(),
            'class_id' => SchoolClass::factory()->create()->getKey(),
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);
    }

    private function addGradeLevel(Subject $subject): void
    {
        $gradeLevel = GradeLevel::create([
            'name' => '1º Ano',
            'stage' => 'fundamental_i',
            'display_order' => 1,
        ]);

        $subject->gradeLevels()->attach($gradeLevel);
    }

    private function addSubject(SchoolClass $schoolClass): void
    {
        $schoolClass->subjects()->attach(Subject::factory()->create());
    }
}
