<?php

namespace Tests\Feature\Teachers;

use App\Enums\ClassStatus;
use App\Filament\Pages\Teacher\TeacherGrades;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SystemParameter;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\GradeCalculationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherGradesExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('professor'));
    }

    public function test_only_open_classes_from_current_year_are_visible_and_old_assessments_cannot_be_saved(): void
    {
        $teacher = $this->teacher();
        $activeYear = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $oldYear = SchoolYear::factory()->forYear(now()->year - 1)->create();
        $activeClass = SchoolClass::factory()->create(['school_year_id' => $activeYear->id, 'name' => 'Turma atual']);
        $oldClass = SchoolClass::factory()->create(['school_year_id' => $oldYear->id, 'name' => 'Turma anterior']);
        $closedClass = SchoolClass::factory()->create(['school_year_id' => $activeYear->id, 'name' => 'Turma fechada', 'status' => ClassStatus::CLOSED]);
        $subject = Subject::factory()->create(['code' => 'MAT-GRADE', 'normalized_code' => 'mat-grade', 'name' => 'Matemática']);

        foreach ([$activeClass, $oldClass, $closedClass] as $class) {
            TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        }
        $currentAssessment = Assessment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $activeClass->id, 'subject_id' => $subject->id, 'school_year_id' => $activeYear->id]);
        $oldAssessment = Assessment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $oldClass->id, 'subject_id' => $subject->id, 'school_year_id' => $oldYear->id]);
        $student = Student::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $activeClass->id, 'school_year_id' => $activeYear->id]);

        $page = Livewire::test(TeacherGrades::class)
            ->assertSet('selectedClassId', $activeClass->id)
            ->assertSet('selectedAssessmentId', $currentAssessment->id);
        $this->assertSame([$activeClass->id], array_keys($page->instance()->getPageData()['classes']));
        $this->assertNotContains($oldAssessment->id, array_keys($page->instance()->getPageData()['assessments']));

        $page->set('selectedClassId', $oldClass->id)
            ->set('selectedSubjectId', $subject->id)
            ->set('selectedAssessmentId', $oldAssessment->id)
            ->call('saveDraft')
            ->assertHasErrors();
        $this->assertDatabaseCount('grades', 0);
    }

    public function test_draft_can_be_partial_and_school_average_changes_the_reference(): void
    {
        $teacher = $this->teacher();
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = Subject::factory()->create(['code' => 'PORT-GRADE', 'normalized_code' => 'port-grade', 'name' => 'Português']);
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        Assessment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'school_year_id' => $year->id]);
        $first = Student::factory()->create();
        $second = Student::factory()->create();
        foreach ([$first, $second] as $student) {
            Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $class->id, 'school_year_id' => $year->id]);
        }
        SystemParameter::create(['key' => 'academic.minimum_grade', 'name' => 'Média mínima', 'category' => 'Regras acadêmicas', 'type' => 'decimal', 'value' => '7.0', 'default_value' => '6.0', 'is_active' => true, 'is_system' => true]);
        $this->assertSame(7.0, app(GradeCalculationService::class)->minimumApproval());

        Livewire::test(TeacherGrades::class)
            ->set('gradeRows.'.$first->id.'.score', 6)
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grades', ['student_id' => $first->id, 'score' => 6]);
        $this->assertSame(0, Grade::query()->where('student_id', $second->id)->count());

        Livewire::test(TeacherGrades::class)
            ->set('gradeRows.'.$first->id.'.score', '')
            ->call('saveDraft')
            ->assertHasNoErrors();
        $this->assertSame(0, Grade::query()->where('student_id', $first->id)->count());
    }

    private function teacher(): Teacher
    {
        $teacher = Teacher::factory()->create();
        $user = $teacher->user;
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        foreach (['teacher.grades.view', 'teacher.grades.create', 'teacher.grades.update'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user, 'teacher');

        return $teacher;
    }
}
