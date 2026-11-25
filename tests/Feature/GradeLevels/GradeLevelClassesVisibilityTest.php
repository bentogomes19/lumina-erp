<?php

namespace Tests\Feature\GradeLevels;

use App\Filament\Resources\GradeLevels\Pages\EditGradeLevel;
use App\Filament\Resources\GradeLevels\RelationManagers\ClassesRelationManager;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GradeLevelClassesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private GradeLevel $sixthGrade;
    private GradeLevel $firstGradeHighSchool;
    private SchoolYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('lumina'));

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->sixthGrade = GradeLevel::create(['name' => '6º Ano', 'stage' => 'fundamental_ii', 'display_order' => 6]);
        $this->firstGradeHighSchool = GradeLevel::create(['name' => '1ª Série', 'stage' => 'medio', 'display_order' => 10]);
        $this->year = SchoolYear::factory()->create(['is_active' => true, 'status' => 'ativo']);
    }

    public function test_grade_level_shows_its_classes_with_link_to_each_class(): void
    {
        $sixthA = $this->schoolClass('6º Ano A', $this->sixthGrade);
        $sixthB = $this->schoolClass('6º Ano B', $this->sixthGrade);
        $highSchool = $this->schoolClass('1ª Série A', $this->firstGradeHighSchool);

        Livewire::test(ClassesRelationManager::class, [
            'ownerRecord' => $this->sixthGrade,
            'pageClass' => EditGradeLevel::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$sixthA, $sixthB])
            ->assertCanNotSeeTableRecords([$highSchool])
            ->assertSeeHtml(e(SchoolClassResource::getUrl('edit', ['record' => $sixthA])));
    }

    public function test_class_list_shows_stage_and_filters_by_grade_and_stage(): void
    {
        $sixthA = $this->schoolClass('6º Ano A', $this->sixthGrade);
        $highSchool = $this->schoolClass('1ª Série A', $this->firstGradeHighSchool);

        Livewire::test(ListSchoolClasses::class)
            ->assertCanSeeTableRecords([$sixthA, $highSchool])
            ->assertSee('Ensino Fundamental II')
            ->assertSee('Ensino Médio')
            ->filterTable('grade_level_id', $this->sixthGrade->id)
            ->assertCanSeeTableRecords([$sixthA])
            ->assertCanNotSeeTableRecords([$highSchool])
            ->resetTableFilters()
            ->filterTable('stage', 'medio')
            ->assertCanSeeTableRecords([$highSchool])
            ->assertCanNotSeeTableRecords([$sixthA]);
    }

    public function test_student_list_shows_grade_and_stage_of_active_enrollment(): void
    {
        $sixthA = $this->schoolClass('6º Ano A', $this->sixthGrade);
        $enrolled = Student::factory()->create(['name' => 'Aluno Matriculado']);
        $notEnrolled = Student::factory()->create(['name' => 'Aluno Sem Turma']);

        Enrollment::factory()->create([
            'student_id' => $enrolled->id,
            'class_id' => $sixthA->id,
            'school_year_id' => $this->year->id,
            'status' => 'Ativa',
        ]);

        Livewire::test(ListStudents::class)
            ->assertCanSeeTableRecords([$enrolled, $notEnrolled])
            ->assertTableColumnStateSet('currentActiveEnrollment.schoolClass.gradeLevel.name', '6º Ano', $enrolled)
            ->assertSee('Ensino Fundamental II');
    }

    private function schoolClass(string $name, GradeLevel $gradeLevel): SchoolClass
    {
        return SchoolClass::factory()->create([
            'name' => $name,
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $this->year->id,
        ]);
    }
}
