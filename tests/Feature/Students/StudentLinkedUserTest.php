<?php

namespace Tests\Feature\Students;

use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Users\UserResource;
use App\Models\Permission;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentLinkedUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    public function test_admin_sees_linked_user_and_can_open_its_profile(): void
    {
        $this->actingAs($this->admin());

        $account = User::factory()->create(['email' => 'aluno.vinculado@lumina.test']);
        $withUser = Student::factory()->create(['user_id' => $account->id]);
        $withoutUser = Student::factory()->create(['user_id' => null]);
        $profileUrl = UserResource::getUrl('edit', ['record' => $account]);

        Livewire::test(ListStudents::class)
            ->assertCanSeeTableRecords([$withUser, $withoutUser])
            ->assertTableColumnStateSet('user.email', 'aluno.vinculado@lumina.test', $withUser)
            ->assertSeeHtml(e($profileUrl))
            ->assertSee('Sem usuário');

        Livewire::test(EditStudent::class, ['record' => $withUser->getRouteKey()])
            ->assertActionVisible('verUsuario')
            ->assertActionHasUrl('verUsuario', $profileUrl);

        Livewire::test(EditStudent::class, ['record' => $withoutUser->getRouteKey()])
            ->assertActionHidden('verUsuario');
    }

    public function test_link_to_user_profile_is_hidden_without_user_permission(): void
    {
        $operator = User::factory()->create();

        foreach (['academic.students.view_any', 'academic.students.view', 'academic.students.update'] as $permission) {
            $operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->actingAs($operator);

        $account = User::factory()->create(['email' => 'aluno.vinculado@lumina.test']);
        $student = Student::factory()->create(['user_id' => $account->id]);

        Livewire::test(ListStudents::class)
            ->assertTableColumnStateSet('user.email', 'aluno.vinculado@lumina.test', $student)
            ->assertDontSeeHtml(e(UserResource::getUrl('edit', ['record' => $account])));

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->assertActionHidden('verUsuario');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
