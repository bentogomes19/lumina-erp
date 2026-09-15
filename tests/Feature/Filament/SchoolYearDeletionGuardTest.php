<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SchoolYears\Pages\EditSchoolYear;
use App\Filament\Resources\SchoolYears\Pages\ListSchoolYears;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearTerm;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolYearDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
        $this->actingAs($this->authorizedUser());
    }

    public function test_school_year_with_links_cannot_be_soft_deleted(): void
    {
        $schoolYear = SchoolYear::factory()->create();
        $this->addTerm($schoolYear);

        Livewire::test(EditSchoolYear::class, ['record' => $schoolYear->getKey()])
            ->assertSuccessful()
            ->callAction(DeleteAction::class)
            ->assertNotified('Exclusão do ano letivo bloqueada');

        $this->assertNull($schoolYear->fresh()->deleted_at);
    }

    public function test_empty_school_year_can_be_soft_deleted(): void
    {
        $schoolYear = SchoolYear::factory()->create();

        Livewire::test(EditSchoolYear::class, ['record' => $schoolYear->getKey()])
            ->assertSuccessful()
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('school_years', ['id' => $schoolYear->getKey()]);
    }

    public function test_bulk_delete_keeps_linked_year_and_soft_deletes_empty_year(): void
    {
        $linkedYear = SchoolYear::factory()->create();
        $emptyYear = SchoolYear::factory()->forYear(now()->year + 1)->create();
        $this->addTerm($linkedYear);

        Livewire::test(ListSchoolYears::class)
            ->assertSuccessful()
            ->callTableBulkAction('delete', [$linkedYear, $emptyYear])
            ->assertNotified('Exclusão de anos letivos');

        $this->assertNull($linkedYear->fresh()->deleted_at);
        $this->assertSoftDeleted('school_years', ['id' => $emptyYear->getKey()]);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach ([
            'academic.school_years.view_any',
            'academic.school_years.update',
            'academic.school_years.delete',
        ] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        return $user;
    }

    private function addTerm(SchoolYear $schoolYear): void
    {
        SchoolYearTerm::create([
            'school_year_id' => $schoolYear->getKey(),
            'name' => '1º Bimestre',
            'type' => 'bimestre',
            'sequence' => 1,
            'starts_at' => "{$schoolYear->year}-02-01",
            'ends_at' => "{$schoolYear->year}-04-30",
        ]);
    }
}
