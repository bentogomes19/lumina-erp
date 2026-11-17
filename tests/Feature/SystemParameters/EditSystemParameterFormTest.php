<?php

namespace Tests\Feature\SystemParameters;

use App\Filament\Resources\SystemParameters\Pages\ListSystemParameters;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemParameter;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditSystemParameterFormTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel, o administrador e os parâmetros como no banco migrado.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::findOrCreate('system.settings.manage', 'web'));
        $user = User::factory()->create(['force_password_change' => false]);
        $user->syncRoles([$role]);
        $this->actingAs($user);

        $this->parameter('class.number_format', 'Formato do número da turma', 'Matrículas e cadastros', 'TUR-{year}-{grade}-{sequence}');
        $this->parameter('institution.primary_color', 'Cor institucional', 'Identidade e personalização', '#3D5A80');
    }

    /**
     * O modal abre com o formato atual e aceita uma máscara escrita pela escola.
     *
     * @return void
     */
    public function test_school_can_write_its_own_class_number_format(): void {
        $record = SystemParameter::where('key', 'class.number_format')->firstOrFail();

        Livewire::test(ListSystemParameters::class)
            ->mountAction(TestAction::make('edit')->table($record))
            ->assertSchemaStateSet(['value' => 'TUR-{year}-{grade}-{sequence}'])
            ->fillForm(['value' => 'T{year}{shift}-{sequence}'])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertSame('T{year}{shift}-{sequence}', $record->fresh()->value);
    }

    /**
     * Uma máscara sem {sequence} é recusada com mensagem e não é salva.
     *
     * @return void
     */
    public function test_format_without_sequence_is_rejected(): void {
        $record = SystemParameter::where('key', 'class.number_format')->firstOrFail();

        Livewire::test(ListSystemParameters::class)
            ->mountAction(TestAction::make('edit')->table($record))
            ->fillForm(['value' => 'TUR-{year}'])
            ->callMountedAction()
            ->assertHasFormErrors(['value']);

        $this->assertSame('TUR-{year}-{grade}-{sequence}', $record->fresh()->value);
    }

    /**
     * A cor institucional é escolhida pelo seletor de cor e salva em hexadecimal.
     *
     * @return void
     */
    public function test_primary_color_is_edited_with_the_color_picker(): void {
        $record = SystemParameter::where('key', 'institution.primary_color')->firstOrFail();

        Livewire::test(ListSystemParameters::class)
            ->mountAction(TestAction::make('edit')->table($record))
            ->assertSchemaStateSet(['color_value' => '#3D5A80'])
            ->fillForm(['color_value' => '#1f7a4d'])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertSame('#1F7A4D', $record->fresh()->value);
    }

    /**
     * Valor que não é uma cor é recusado.
     *
     * @return void
     */
    public function test_invalid_color_is_rejected(): void {
        $record = SystemParameter::where('key', 'institution.primary_color')->firstOrFail();

        Livewire::test(ListSystemParameters::class)
            ->mountAction(TestAction::make('edit')->table($record))
            ->fillForm(['color_value' => 'azul'])
            ->callMountedAction()
            ->assertHasFormErrors(['color_value']);

        $this->assertSame('#3D5A80', $record->fresh()->value);
    }

    /**
     * Cadastra um parâmetro de texto como fica após a migração.
     *
     * @param string $key
     * @param string $name
     * @param string $category
     * @param string $value
     *
     * @return void
     */
    private function parameter(string $key, string $name, string $category, string $value): void {
        SystemParameter::create([
            'key'           => $key,
            'name'          => $name,
            'category'      => $category,
            'type'          => 'text',
            'value'         => $value,
            'default_value' => $value,
            'description'   => 'Parâmetro de teste.',
            'is_active'     => true,
            'is_system'     => true,
        ]);
    }

}
