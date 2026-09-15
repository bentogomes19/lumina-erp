<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentAuditLogDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    public function test_database_rejects_force_delete_when_enrollment_has_an_audit_log(): void
    {
        $enrollment = Enrollment::factory()->create();
        $logId = $enrollment->logs()->firstOrFail()->getKey();

        try {
            DB::transaction(fn () => $enrollment->forceDelete());
            $this->fail('A FK deveria impedir a exclusão física de uma matrícula com logs.');
        } catch (QueryException) {
            // Comportamento esperado da FK RESTRICT.
        }

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->getKey()]);
        $this->assertDatabaseHas('enrollment_logs', ['id' => $logId]);
    }

    public function test_filament_exposes_only_soft_delete_and_preserves_the_audit_log(): void
    {
        $user = $this->authorizedUser();
        $enrollment = Enrollment::factory()->create();
        $logId = $enrollment->logs()->firstOrFail()->getKey();
        $this->actingAs($user);

        Livewire::test(EditEnrollment::class, ['record' => $enrollment->getKey()])
            ->assertSuccessful()
            ->assertActionDoesNotExist(ForceDeleteAction::class)
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('enrollments', ['id' => $enrollment->getKey()]);
        $this->assertDatabaseHas('enrollment_logs', ['id' => $logId]);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach ([
            'academic.enrollments.view_any',
            'academic.enrollments.update',
            'academic.enrollments.cancel',
        ] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        return $user;
    }
}
