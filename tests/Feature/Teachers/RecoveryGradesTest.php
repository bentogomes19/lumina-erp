<?php

namespace Tests\Feature\Teachers;

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
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\GradeCalculationService;
use App\Services\TeacherRosterService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecoveryGradesTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;

    private SchoolClass $class;

    private Subject $subject;

    private Assessment $exam;

    /** @var array<string, Enrollment> */
    private array $enrollments = [];

    /**
     * Turma com uma prova publicada no 1º bimestre (média da escola 6,0):
     * Ana 4,0 e Caio 5,5 abaixo da média; Bia 8,0 acima; Davi sem nota; Eva com 3,0 só em rascunho.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('professor'));

        $this->teacher = Teacher::factory()->create();
        $user = $this->teacher->user;
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        foreach (['teacher.grades.view', 'teacher.grades.create', 'teacher.grades.update', 'teacher.grades.publish'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user, 'teacher');

        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $this->class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $this->subject = Subject::factory()->create(['code' => 'MAT-REC', 'normalized_code' => 'mat-rec', 'name' => 'Matemática']);
        TeacherAssignment::create(['teacher_id' => $this->teacher->id, 'class_id' => $this->class->id, 'subject_id' => $this->subject->id]);

        $this->exam = $this->assessment('test', 'Prova 1');

        foreach (['ana' => 4.0, 'bia' => 8.0, 'caio' => 5.5, 'davi' => null, 'eva' => 3.0] as $name => $score) {
            $student = Student::factory()->create(['name' => ucfirst($name)]);
            $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $this->class->id, 'school_year_id' => $year->id]);
            $this->enrollments[$name] = $enrollment;

            if ($score !== null) {
                Grade::factory()->forAssessment($this->exam, $enrollment)->create([
                    'score' => $score,
                    'term' => 'b1',
                    'assessment_type' => 'test',
                    'locked_at' => $name === 'eva' ? null : now(),
                ]);
            }
        }
    }

    public function test_recovery_lists_only_students_below_the_average_with_published_grades(): void
    {
        $recovery = $this->assessment('recovery', 'Recuperação 1º bimestre');

        $names = app(TeacherRosterService::class)->forAssessment($recovery)->map(fn (Enrollment $e) => $e->student->name)->all();

        $this->assertSame(['Ana', 'Caio'], $names);
    }

    public function test_regular_assessment_still_lists_every_student(): void
    {
        $this->assertCount(5, app(TeacherRosterService::class)->forAssessment($this->exam));
    }

    public function test_recovery_is_published_with_only_the_students_who_need_it(): void
    {
        $recovery = $this->assessment('recovery', 'Recuperação 1º bimestre');

        Livewire::test(TeacherGrades::class)
            ->set('selectedClassId', $this->class->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedAssessmentId', $recovery->id)
            ->set('gradeRows.'.$this->enrollments['ana']->student_id.'.score', 7)
            ->set('gradeRows.'.$this->enrollments['caio']->student_id.'.score', 5)
            ->call('publishGrades')
            ->assertHasNoErrors();

        $published = Grade::published()->where('assessment_id', $recovery->id)->pluck('score', 'student_id');
        $this->assertCount(2, $published);
        $this->assertEquals(7, $published[$this->enrollments['ana']->student_id]);

        /* No boletim, a recuperação substitui a média do bimestre só quando é maior. */
        $calculator = app(GradeCalculationService::class);
        $ana = $calculator->subjectReport(Grade::published()->where('student_id', $this->enrollments['ana']->student_id)->get());
        $caio = $calculator->subjectReport(Grade::published()->where('student_id', $this->enrollments['caio']->student_id)->get());

        $this->assertSame(7.0, $ana['terms']['b1']['final_average']);
        $this->assertSame(5.5, $caio['terms']['b1']['final_average']);
    }

    public function test_student_who_already_has_a_recovery_grade_stays_in_the_list(): void
    {
        $recovery = $this->assessment('recovery', 'Recuperação 1º bimestre');
        Grade::factory()->forAssessment($recovery, $this->enrollments['bia'])->create(['score' => 9, 'term' => 'b1', 'assessment_type' => 'recovery']);

        $names = app(TeacherRosterService::class)->forAssessment($recovery)->map(fn (Enrollment $e) => $e->student->name)->all();

        $this->assertSame(['Ana', 'Bia', 'Caio'], $names);
    }

    public function test_recovery_without_students_below_average_cannot_be_saved(): void
    {
        Grade::query()->where('class_id', $this->class->id)->update(['score' => 9]);
        $recovery = $this->assessment('recovery', 'Recuperação 1º bimestre');

        Livewire::test(TeacherGrades::class)
            ->set('selectedClassId', $this->class->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedAssessmentId', $recovery->id)
            ->assertSee('Nenhum aluno precisa desta recuperação.')
            ->call('publishGrades')
            ->assertHasErrors('assessment');

        $this->assertSame(0, Grade::query()->where('assessment_id', $recovery->id)->count());
    }

    private function assessment(string $type, string $title): Assessment
    {
        return Assessment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'school_year_id' => $this->class->school_year_id,
            'title' => $title,
            'assessment_type' => $type,
        ]);
    }
}
