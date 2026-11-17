<?php

namespace Tests\Feature\SchoolClasses;

use App\Filament\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\SystemParameter;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CreateSchoolClassFormTest extends TestCase {

    use RefreshDatabase;

    private SchoolYear $schoolYear;

    private GradeLevel $gradeLevel;

    /**
     * Prepara o painel, o administrador e o contexto acadêmico.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['academic.classes.view_any', 'academic.classes.view', 'academic.classes.create', 'academic.classes.update'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create(['force_password_change' => false]);
        $user->syncRoles([$role]);
        $this->actingAs($user);

        SystemParameter::create([
            'key'           => 'class.number_format',
            'name'          => 'Formato do número da turma',
            'category'      => 'Matrículas e cadastros',
            'type'          => 'select',
            'value'         => 'TUR-{year}-{grade}-{sequence}',
            'default_value' => 'TUR-{year}-{grade}-{sequence}',
            'description'   => 'Regra que define o código automático de cada turma.',
            'is_active'     => true,
            'is_system'     => true,
        ]);

        $this->schoolYear = SchoolYear::factory()->forYear(2026)->create();
        $this->gradeLevel = GradeLevel::firstOrCreate(['name' => '1º Ano'], ['stage' => 'fundamental_i', 'display_order' => 1]);
    }

    /**
     * Cadastra a turma pela tela com o código em branco, como na secretaria.
     *
     * @return void
     */
    public function test_creates_class_with_generated_code_from_the_form(): void {
        Livewire::test(CreateSchoolClass::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('TUR-2026-01-01', SchoolClass::where('name', '1 ANO B')->value('code'));
    }

    /**
     * Turma com mesmo nome, série, ano e turno mostra erro no formulário em vez de erro 500.
     *
     * @return void
     */
    public function test_duplicate_class_shows_form_error(): void {
        $this->existingClass('1 ANO B');

        Livewire::test(CreateSchoolClass::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertSame(1, SchoolClass::withTrashed()->where('name', '1 ANO B')->count());
    }

    /**
     * Turma excluída com o mesmo nome também bloqueia, pois continua no banco.
     *
     * @return void
     */
    public function test_duplicate_of_deleted_class_shows_form_error(): void {
        $this->existingClass('1 ANO B')->delete();

        Livewire::test(CreateSchoolClass::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    /**
     * Mesmo nome em outro turno é permitido.
     *
     * @return void
     */
    public function test_same_name_in_another_shift_is_allowed(): void {
        $this->existingClass('1 ANO B', 'afternoon');

        Livewire::test(CreateSchoolClass::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();
    }

    /**
     * Salvar uma turma existente sem trocar o nome não acusa duplicidade com ela mesma.
     *
     * @return void
     */
    public function test_editing_class_keeps_its_own_name(): void {
        $class = $this->existingClass('1 ANO B');

        Livewire::test(EditSchoolClass::class, ['record' => $class->getRouteKey()])
            ->fillForm(['capacity' => 30])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(30, $class->fresh()->capacity);
    }

    /**
     * Dados preenchidos na tela de criação.
     *
     * @return array<string, mixed>
     */
    private function formData(): array {
        return [
            'code'           => null,
            'name'           => '1 ANO B',
            'grade_level_id' => $this->gradeLevel->id,
            'school_year_id' => $this->schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'status'         => 'open',
            'capacity'       => 35,
        ];
    }

    /**
     * Cria uma turma já existente no mesmo contexto.
     *
     * @param string $name
     * @param string $shift
     *
     * @return SchoolClass
     */
    private function existingClass(string $name, string $shift = 'morning'): SchoolClass {
        return SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'code'           => 'TUR-2026-01-A',
            'name'           => $name,
            'grade_level_id' => $this->gradeLevel->id,
            'school_year_id' => $this->schoolYear->id,
            'shift'          => $shift,
            'type'           => 'regular',
            'status'         => 'open',
            'capacity'       => 35,
        ]);
    }

}
