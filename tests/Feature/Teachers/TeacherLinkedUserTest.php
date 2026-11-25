<?php

namespace Tests\Feature\Teachers;

use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Permission;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherLinkedUserTest extends TestCase
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

        $account = User::factory()->create(['email' => 'professor.vinculado@lumina.test']);
        $withUser = Teacher::factory()->create(['user_id' => $account->id]);
        $withoutUser = Teacher::factory()->create(['user_id' => null]);
        $profileUrl = UserResource::getUrl('edit', ['record' => $account]);

        Livewire::test(ListTeachers::class)
            ->assertCanSeeTableRecords([$withUser, $withoutUser])
            ->assertTableColumnStateSet('user.email', 'professor.vinculado@lumina.test', $withUser)
            ->assertSeeHtml(e($profileUrl))
            ->assertSee('Sem usuário');

        Livewire::test(EditTeacher::class, ['record' => $withUser->getRouteKey()])
            ->assertActionVisible('verUsuario')
            ->assertActionHasUrl('verUsuario', $profileUrl);

        Livewire::test(EditTeacher::class, ['record' => $withoutUser->getRouteKey()])
            ->assertActionHidden('verUsuario');
    }

    public function test_link_to_user_profile_is_hidden_without_user_permission(): void
    {
        $operator = User::factory()->create();

        foreach (['admin.teachers.view_any', 'admin.teachers.update'] as $permission) {
            $operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->actingAs($operator);

        $account = User::factory()->create(['email' => 'professor.vinculado@lumina.test']);
        $teacher = Teacher::factory()->create(['user_id' => $account->id]);

        Livewire::test(ListTeachers::class)
            ->assertTableColumnStateSet('user.email', 'professor.vinculado@lumina.test', $teacher)
            ->assertDontSeeHtml(e(UserResource::getUrl('edit', ['record' => $account])));

        Livewire::test(EditTeacher::class, ['record' => $teacher->getRouteKey()])
            ->assertActionHidden('verUsuario');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
