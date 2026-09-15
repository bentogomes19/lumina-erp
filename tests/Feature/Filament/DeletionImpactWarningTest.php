<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeletionImpactWarningTest extends TestCase
{
    use RefreshDatabase;

    private int $subjectSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    #[DataProvider('resources')]
    public function test_individual_soft_delete_warns_about_links_and_still_deletes(
        string $type,
        string $editPage,
        string $listPage,
        string $permissionPrefix,
        string $pluralLabel,
        string $title,
        string $bulkTitle,
        string $link,
    ): void {
        $this->actingAs($this->authorizedUser($permissionPrefix));
        $record = $this->createRecord($type, 'Com vínculo');
        $this->addLink($type, $record);

        Livewire::test($editPage, ['record' => $record->getKey()])
            ->assertSuccessful()
            ->callAction(DeleteAction::class)
            ->assertNotified(
                Notification::make()
                    ->title($title)
                    ->body($link)
                    ->warning(),
            );

        $this->assertSoftDeleted($record);
    }

    #[DataProvider('resources')]
    public function test_individual_soft_delete_without_links_does_not_warn_and_still_deletes(
        string $type,
        string $editPage,
        string $listPage,
        string $permissionPrefix,
        string $pluralLabel,
        string $title,
        string $bulkTitle,
    ): void {
        $this->actingAs($this->authorizedUser($permissionPrefix));
        $record = $this->createRecord($type, 'Sem vínculo');

        Livewire::test($editPage, ['record' => $record->getKey()])
            ->assertSuccessful()
            ->callAction(DeleteAction::class)
            ->assertNotNotified($title);

        $this->assertSoftDeleted($record);
    }

    #[DataProvider('resources')]
    public function test_bulk_soft_delete_warns_once_and_deletes_linked_and_unlinked_records(
        string $type,
        string $editPage,
        string $listPage,
        string $permissionPrefix,
        string $pluralLabel,
        string $title,
        string $bulkTitle,
        string $link,
    ): void {
        $this->actingAs($this->authorizedUser($permissionPrefix));
        $linked = $this->createRecord($type, 'Com vínculo');
        $free = $this->createRecord($type, 'Sem vínculo');
        $this->addLink($type, $linked);

        Livewire::test($listPage)
            ->assertSuccessful()
            ->callTableBulkAction('delete', [$linked, $free])
            ->assertNotified(
                Notification::make()
                    ->title($bulkTitle)
                    ->body(sprintf(
                        "1 de 2 %s selecionados possuem vínculos ativos (1 vínculo encontrado).\n\n• Com vínculo (#%s): %s",
                        $pluralLabel,
                        $linked->getKey(),
                        $link,
                    ))
                    ->warning(),
            );

        $this->assertSoftDeleted($linked);
        $this->assertSoftDeleted($free);
    }

    #[DataProvider('resources')]
    public function test_bulk_soft_delete_without_links_does_not_warn_and_still_deletes(
        string $type,
        string $editPage,
        string $listPage,
        string $permissionPrefix,
        string $pluralLabel,
        string $title,
        string $bulkTitle,
    ): void {
        $this->actingAs($this->authorizedUser($permissionPrefix));
        $first = $this->createRecord($type, 'Livre A');
        $second = $this->createRecord($type, 'Livre B');

        Livewire::test($listPage)
            ->assertSuccessful()
            ->callTableBulkAction('delete', [$first, $second])
            ->assertNotNotified($bulkTitle);

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    /** @return array<string, array{string, class-string, class-string, string, string, string, string, string}> */
    public static function resources(): array
    {
        return [
            'professor' => [
                'teacher',
                EditTeacher::class,
                ListTeachers::class,
                'admin.teachers',
                'professores',
                'Atenção: professor com vínculos ativos',
                'Atenção: professores com vínculos ativos',
                '1 atribuição',
            ],
            'disciplina' => [
                'subject',
                EditSubject::class,
                ListSubjects::class,
                'academic.subjects',
                'disciplinas',
                'Atenção: disciplina com vínculos ativos',
                'Atenção: disciplinas com vínculos ativos',
                '1 nível/série',
            ],
            'turma' => [
                'class',
                EditSchoolClass::class,
                ListSchoolClasses::class,
                'academic.classes',
                'turmas',
                'Atenção: turma com vínculos ativos',
                'Atenção: turmas com vínculos ativos',
                '1 disciplina',
            ],
        ];
    }

    private function authorizedUser(string $permissionPrefix): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach (["{$permissionPrefix}.view_any", "{$permissionPrefix}.update", "{$permissionPrefix}.delete"] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        return $user;
    }

    private function createRecord(string $type, string $name): Model
    {
        return match ($type) {
            'teacher' => Teacher::factory()->create(['name' => $name]),
            'subject' => Subject::factory()->create([
                'name' => $name,
                'code' => sprintf('TST-%04d', ++$this->subjectSequence),
                'normalized_code' => sprintf('TST%04d', $this->subjectSequence),
            ]),
            'class' => SchoolClass::factory()->create(['name' => $name]),
        };
    }

    private function addLink(string $type, Model $record): void
    {
        match ($type) {
            'teacher' => TeacherAssignment::create([
                'teacher_id' => $record->getKey(),
                'class_id' => SchoolClass::factory()->create()->getKey(),
                'subject_id' => Subject::factory()->create()->getKey(),
            ]),
            'subject' => $record->gradeLevels()->attach(GradeLevel::create([
                'name' => '1º Ano',
                'stage' => 'fundamental_i',
                'display_order' => 1,
            ])),
            'class' => $record->subjects()->attach(Subject::factory()->create()),
        };
    }
}
