<?php

namespace Tests\Feature\Teachers;

use App\Filament\Pages\Teacher\TeacherAttendance;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SystemParameter;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherAttendanceExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('professor'));
    }

    public function test_only_active_year_classes_and_assigned_subjects_are_offered(): void
    {
        $teacher = $this->teacher();
        $activeYear = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $oldYear = SchoolYear::factory()->forYear(now()->year - 1)->create();
        $activeClass = SchoolClass::factory()->create(['school_year_id' => $activeYear->id, 'name' => 'Turma atual']);
        $oldClass = SchoolClass::factory()->create(['school_year_id' => $oldYear->id, 'name' => 'Turma histórica']);
        $ownSubject = $this->subject('PORT-ATT', 'Português');
        $otherSubject = $this->subject('MAT-ATT', 'Matemática');
        $this->assign($teacher, $activeClass, $ownSubject);
        $this->assign($teacher, $oldClass, $ownSubject);
        $this->assign(Teacher::factory()->create(), $activeClass, $otherSubject);
        $this->lesson($teacher, $activeClass, $ownSubject, '07:00:00');

        $page = Livewire::test(TeacherAttendance::class)->assertSuccessful()
            ->assertSet('selectedClassId', $activeClass->id)
            ->assertSet('selectedSubjectId', $ownSubject->id);
        $data = $page->instance()->getPageData();

        $this->assertSame([$activeClass->id], array_keys($data['classes']));
        $this->assertSame([$ownSubject->id], array_keys($data['subjects']));
        $page->set('selectedClassId', $oldClass->id)->call('saveAttendance')->assertHasErrors();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_new_call_starts_present_and_unchecking_records_absence(): void
    {
        [$teacher, $class, $subject, $student] = $this->context();
        $this->lesson($teacher, $class, $subject, '07:00:00');

        $page = Livewire::test(TeacherAttendance::class)->assertSuccessful()
            ->assertSet('statusSelections.'.$student->id, ['present'])
            ->assertSee('Foto')
            ->assertSee('Presente: '.$student->name);
        $this->assertSame(1, $page->instance()->getPageData()['summary']['present']);

        $page->call('togglePresence', $student->id)
            ->assertSet('statusSelections.'.$student->id, ['absent']);
        $this->assertSame(1, $page->instance()->getPageData()['summary']['absent']);
        $page->call('saveAttendance')->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'absent']);
    }

    public function test_student_details_follow_system_parameter_and_exact_lesson_roster(): void
    {
        [$teacher, $class, $subject, $student] = $this->context();
        $this->lesson($teacher, $class, $subject, '07:00:00');
        $outsider = Student::factory()->create();
        $parameter = SystemParameter::query()->where('key', 'teacher.student_details_enabled')->firstOrFail();

        Livewire::test(TeacherAttendance::class)
            ->assertDontSee('Ver detalhes de '.$student->name)
            ->call('openStudentDetails', $student->id)->assertForbidden();

        $parameter->update(['value' => '1']);
        Livewire::test(TeacherAttendance::class)
            ->assertSee('Ver detalhes de '.$student->name)
            ->call('openStudentDetails', $student->id)
            ->assertSet('selectedStudentId', $student->id)
            ->assertSee('Responsável principal')
            ->call('closeStudentDetails')->assertSet('selectedStudentId', null);
        Livewire::test(TeacherAttendance::class)
            ->call('openStudentDetails', $outsider->id)->assertForbidden();

        $parameter->update(['is_active' => false]);
        Livewire::test(TeacherAttendance::class)
            ->assertDontSee('Ver detalhes de '.$student->name)
            ->call('openStudentDetails', $student->id)->assertForbidden();
    }

    public function test_two_lessons_on_one_day_have_separate_selection(): void
    {
        [$teacher, $class, $subject, $student] = $this->context();
        $first = $this->lesson($teacher, $class, $subject, '07:00:00');
        $second = $this->lesson($teacher, $class, $subject, '08:00:00');

        $page = Livewire::test(TeacherAttendance::class)->assertSet('selectedLessonId', $first->id)
            ->call('togglePresence', $student->id)
            ->assertSet('statusSelections.'.$student->id, ['absent'])
            ->set('selectedLessonId', $second->id)
            ->assertSet('statusSelections.'.$student->id, ['present']);
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_saving_an_existing_call_preserves_late_and_excused_statuses(): void
    {
        [$teacher, $class, $subject, $lateStudent] = $this->context();
        $lesson = $this->lesson($teacher, $class, $subject, '07:00:00');
        $excusedStudent = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $excusedStudent->id,
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
        ]);

        foreach ([$lateStudent->id => 'late', $excusedStudent->id => 'excused'] as $studentId => $status) {
            Attendance::create([
                'student_id' => $studentId,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
                'lesson_id' => $lesson->id,
                'date' => today()->toDateString(),
                'status' => $status,
            ]);
        }

        Livewire::test(TeacherAttendance::class)
            ->assertSet('statusSelections.'.$lateStudent->id, ['late'])
            ->assertSet('statusSelections.'.$excusedStudent->id, ['excused'])
            ->call('saveAttendance')->assertHasNoErrors();

        $this->assertDatabaseHas('attendances', ['student_id' => $lateStudent->id, 'lesson_id' => $lesson->id, 'status' => 'late']);
        $this->assertDatabaseHas('attendances', ['student_id' => $excusedStudent->id, 'lesson_id' => $lesson->id, 'status' => 'excused']);
    }

    private function context(): array
    {
        $teacher = $this->teacher();
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = $this->subject('HIS-ATT', 'História');
        $this->assign($teacher, $class, $subject);
        $student = Student::factory()->create(['guardian_main' => 'Responsável de teste']);
        Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $class->id, 'school_year_id' => $year->id]);

        return [$teacher, $class, $subject, $student];
    }

    private function teacher(): Teacher
    {
        $teacher = Teacher::factory()->create();
        $user = $teacher->user;
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        foreach (['teacher.attendance.view', 'teacher.attendance.create', 'teacher.attendance.update'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user, 'teacher');

        return $teacher;
    }

    private function subject(string $code, string $name): Subject
    {
        return Subject::factory()->create(['code' => $code, 'normalized_code' => strtolower($code), 'name' => $name]);
    }

    private function assign(Teacher $teacher, SchoolClass $class, Subject $subject): void
    {
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
    }

    private function lesson(Teacher $teacher, SchoolClass $class, Subject $subject, string $start): Lesson
    {
        return Lesson::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'school_year_id' => $class->school_year_id,
            'date' => today()->toDateString(),
            'start_time' => $start,
            'end_time' => '09:00:00',
            'status' => 'scheduled',
        ]);
    }
}
