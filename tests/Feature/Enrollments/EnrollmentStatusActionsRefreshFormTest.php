<?php

namespace Tests\Feature\Enrollments;

use App\Enums\EnrollmentLockReason;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Depois de uma ação de status, o campo "Status da Matrícula" precisa mostrar o
 * status gravado, e não o que a página tinha carregado antes da ação.
 */
class EnrollmentStatusActionsRefreshFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('lumina'));

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    public function test_external_transfer_updates_status_field(): void
    {
        $enrollment = $this->activeEnrollment();

        Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
            ->assertSchemaStateSet(['status' => EnrollmentStatus::ACTIVE->value])
            ->callAction('transferirExterna', [
                'transfer_destination' => 'Colégio Destino',
                'data_transferencia' => now()->toDateString(),
                'transfer_reason' => 'Mudança de cidade',
            ])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['status' => EnrollmentStatus::TRANSFERRED_EXTERNAL->value]);

        $this->assertSame(EnrollmentStatus::TRANSFERRED_EXTERNAL, $enrollment->fresh()->status);
    }

    public function test_lock_reactivate_and_cancel_update_status_field(): void
    {
        $enrollment = $this->activeEnrollment();

        Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
            ->callAction('trancar', [
                'locked_reason' => array_key_first(EnrollmentLockReason::options()),
                'observacao' => 'Afastamento temporário',
            ])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['status' => EnrollmentStatus::LOCKED->value])
            ->callAction('reativar', ['observacao' => 'Retorno às aulas'])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['status' => EnrollmentStatus::ACTIVE->value])
            ->callAction('cancelar', [
                'cancel_reason' => 'Desistência',
                'cancel_observations' => 'Pedido do responsável',
            ])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['status' => EnrollmentStatus::CANCELED->value]);
    }

    private function activeEnrollment(): Enrollment
    {
        $year = SchoolYear::factory()->create(['is_active' => true, 'status' => 'ativo']);
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);

        return Enrollment::factory()->create([
            'class_id' => $class->id,
            'school_year_id' => $year->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }
}
