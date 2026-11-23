<?php

namespace Tests\Feature\SchoolClasses;

use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteOpenSchoolClassTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel e um administrador com permissão de excluir turmas.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));

        $user = User::factory()->create(['force_password_change' => false]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['academic.classes.view_any', 'academic.classes.update', 'academic.classes.delete'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
    }

    /**
     * Pelo model, a turma aberta não é excluída.
     *
     * @return void
     */
    public function test_open_class_is_not_deleted_by_the_model(): void {
        $class = SchoolClass::factory()->create(['status' => 'open']);

        $this->assertFalse($class->delete());
        $this->assertNotSoftDeleted($class);
    }

    /**
     * Turma fechada ou arquivada continua podendo ser excluída.
     *
     * @return void
     */
    public function test_closed_and_archived_classes_can_be_deleted(): void {
        $closed   = SchoolClass::factory()->create(['status' => 'closed']);
        $archived = SchoolClass::factory()->create(['status' => 'archived', 'name' => 'Turma arquivada']);

        $closed->delete();
        $archived->delete();

        $this->assertSoftDeleted($closed);
        $this->assertSoftDeleted($archived);
    }

    /**
     * Uma turma aberta que já estava na lixeira (antes da regra) pode ser excluída definitivamente.
     *
     * @return void
     */
    public function test_trashed_open_class_can_still_be_force_deleted(): void {
        $class = SchoolClass::factory()->create(['status' => 'open']);
        $class->deleteQuietly();

        $class->fresh()->forceDelete();

        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    /**
     * O botão Excluir da edição mostra o motivo e não exclui a turma aberta.
     *
     * @return void
     */
    public function test_delete_button_blocks_open_class_with_message(): void {
        $class = SchoolClass::factory()->create(['status' => 'open']);

        Livewire::test(EditSchoolClass::class, ['record' => $class->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified(
                Notification::make()
                    ->title('Exclusão bloqueada: turma aberta')
                    ->body(SchoolClass::DELETE_OPEN_MESSAGE)
                    ->danger(),
            );

        $this->assertNotSoftDeleted($class);
    }

    /**
     * O botão Excluir da edição continua excluindo turma fechada.
     *
     * @return void
     */
    public function test_delete_button_deletes_closed_class(): void {
        $class = SchoolClass::factory()->create(['status' => 'closed']);

        Livewire::test(EditSchoolClass::class, ['record' => $class->getKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($class);
    }

    /**
     * Na exclusão em massa, uma turma aberta na seleção bloqueia a ação inteira.
     *
     * @return void
     */
    public function test_bulk_delete_is_blocked_when_any_selected_class_is_open(): void {
        $open   = SchoolClass::factory()->create(['status' => 'open', 'name' => 'Turma aberta']);
        $closed = SchoolClass::factory()->create(['status' => 'closed', 'name' => 'Turma fechada']);

        Livewire::test(ListSchoolClasses::class)
            ->callTableBulkAction('delete', [$open, $closed])
            ->assertNotified('Exclusão bloqueada: turma aberta');

        $this->assertNotSoftDeleted($open);
        $this->assertNotSoftDeleted($closed);
    }

    /**
     * Sem turma aberta na seleção, a exclusão em massa funciona.
     *
     * @return void
     */
    public function test_bulk_delete_works_for_closed_classes(): void {
        $first  = SchoolClass::factory()->create(['status' => 'closed', 'name' => 'Fechada 1']);
        $second = SchoolClass::factory()->create(['status' => 'archived', 'name' => 'Arquivada 2']);

        Livewire::test(ListSchoolClasses::class)
            ->callTableBulkAction('delete', [$first, $second]);

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

}
