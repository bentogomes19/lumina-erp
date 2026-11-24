<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * O seletor de data do Filament (não nativo) é somente leitura: não aceita
 * digitação. Os campos de nascimento usam o seletor nativo do navegador.
 */
class BirthDateFieldsAreTypableTest extends TestCase
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

    public function test_birth_date_fields_accept_typing(): void
    {
        $cases = [
            [CreateEnrollment::class, [], 'student_birth_date'],
            [CreateStudent::class, [], 'birth_date'],
            [CreateTeacher::class, [], 'birth_date'],
            [EditTeacher::class, ['record' => Teacher::factory()->create()->getRouteKey()], 'birth_date'],
            [CreateUser::class, [], 'birth_date'],
        ];

        foreach ($cases as [$page, $params, $field]) {
            Livewire::test($page, $params)
                ->assertFormFieldExists(
                    $field,
                    fn (DatePicker $picker): bool => $picker->isNative(),
                );
        }
    }
}
