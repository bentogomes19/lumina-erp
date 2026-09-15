<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeletionGuardForceDeleteBulkActionTest extends TestCase
{
    use RefreshDatabase;

    private int $subjectSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    #[DataProvider('resources')]
    public function test_bulk_force_delete_removes_only_unlinked_records(
        string $type,
        string $page,
        string $permissionPrefix,
        string $firstReason,
        string $secondReason,
    ): void {
        $this->actingAs($this->authorizedUser($permissionPrefix));
        $records = $this->trashedRecordsWithDifferentLinks($type);

        Livewire::test($page)
            ->assertSuccessful()
            ->filterTable('trashed', false)
            ->callTableBulkAction('forceDelete', $records);

        $this->assertNull($records[0]->newQueryWithoutScopes()->find($records[0]->getKey()));
        $this->assertNotNull($records[1]->newQueryWithoutScopes()->find($records[1]->getKey()));
        $this->assertNotNull($records[2]->newQueryWithoutScopes()->find($records[2]->getKey()));
        $notifications = session('filament.claimed_notifications')
            ?? session('filament.notifications', []);
        $this->assertCount(1, $notifications);

        Notification::assertNotified(
            Notification::make()
                ->title('Exclusão definitiva em lote')
                ->body(sprintf(
                    "1 registro excluído e 2 registros bloqueados.\n\nBloqueados:\n• Bloqueado A (#%s): %s\n• Bloqueado B (#%s): %s",
                    $records[1]->getKey(),
                    $firstReason,
                    $records[2]->getKey(),
                    $secondReason,
                ))
                ->warning(),
        );
    }

    /** @return array<string, array{string, class-string, string, string, string}> */
    public static function resources(): array
    {
        return [
            'professores' => ['teacher', ListTeachers::class, 'admin.teachers', '1 atribuição', '1 avaliação'],
            'alunos' => ['student', ListStudents::class, 'academic.students', '1 registro de frequência', '1 nota lançada'],
            'disciplinas' => ['subject', ListSubjects::class, 'academic.subjects', '1 nível/série', '1 turma'],
            'turmas' => ['class', ListSchoolClasses::class, 'academic.classes', '1 disciplina', '1 avaliação'],
        ];
    }

    private function authorizedUser(string $permissionPrefix): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach (["{$permissionPrefix}.view_any", "{$permissionPrefix}.delete"] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        return $user;
    }

    /** @return Collection<int, Model> */
    private function trashedRecordsWithDifferentLinks(string $type): Collection
    {
        $records = collect(['Livre', 'Bloqueado A', 'Bloqueado B'])
            ->map(fn (string $name, int $index): Model => $this->createRecord($type, $name, $index));

        $this->addFirstLink($type, $records[1]);
        $this->addSecondLink($type, $records[2]);
        $records->each->delete();

        return $records;
    }

    private function createRecord(string $type, string $name, int $index): Model
    {
        return match ($type) {
            'teacher' => Teacher::factory()->create([
                'name' => $name,
                'employee_number' => "BULK-PROF-{$index}",
            ]),
            'student' => Student::factory()->create([
                'name' => $name,
                'registration_number' => "BULK-ALU-{$index}",
            ]),
            'subject' => Subject::factory()->create([
                'name' => $name,
                'code' => "BULK-DISC-{$index}",
            ]),
            'class' => SchoolClass::factory()->create([
                'name' => $name,
                'code' => "BULK-TURMA-{$index}",
            ]),
        };
    }

    private function addFirstLink(string $type, Model $record): void
    {
        match ($type) {
            'teacher' => TeacherAssignment::create([
                'teacher_id' => $record->getKey(),
                'class_id' => SchoolClass::factory()->create()->getKey(),
                'subject_id' => $this->auxiliarySubject()->getKey(),
            ]),
            'student' => Attendance::create([
                'student_id' => $record->getKey(),
                'class_id' => SchoolClass::factory()->create()->getKey(),
                'date' => now()->toDateString(),
                'status' => 'present',
            ]),
            'subject' => $record->gradeLevels()->attach(GradeLevel::create([
                'name' => '1º Ano',
                'stage' => 'fundamental_i',
                'display_order' => 1,
            ])),
            'class' => $record->subjects()->attach($this->auxiliarySubject()),
        };
    }

    private function addSecondLink(string $type, Model $record): void
    {
        match ($type) {
            'teacher' => Assessment::factory()->create([
                'teacher_id' => $record->getKey(),
                'subject_id' => $this->auxiliarySubject()->getKey(),
            ]),
            'student' => $this->addGrade($record),
            'subject' => SchoolClass::factory()->create()->subjects()->attach($record),
            'class' => Assessment::factory()->create([
                'class_id' => $record->getKey(),
                'subject_id' => $this->auxiliarySubject()->getKey(),
            ]),
        };
    }

    private function addGrade(Student $student): void
    {
        Grade::create([
            'student_id' => $student->getKey(),
            'class_id' => SchoolClass::factory()->create()->getKey(),
            'subject_id' => $this->auxiliarySubject()->getKey(),
            'teacher_id' => Teacher::factory()->create()->getKey(),
            'score' => 8,
            'max_score' => 10,
            'weight' => 1,
            'term' => 'b1',
            'assessment_type' => 'test',
            'sequence' => 1,
            'origin' => 'manual',
        ]);
    }

    private function auxiliarySubject(): Subject
    {
        $this->subjectSequence++;

        return Subject::factory()->create([
            'name' => "Disciplina auxiliar {$this->subjectSequence}",
            'code' => "AUX{$this->subjectSequence}",
        ]);
    }
}
