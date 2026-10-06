<?php

namespace Tests\Feature\Teachers;

use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Modules\Assessments\Application\RecordTeacherGrades;
use App\Modules\Attendance\Application\RecordTeacherAttendance;
use App\Services\CurrentTeacherService;
use App\Services\TeacherRosterService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherPortalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_sabbatical_and_terminated_teachers_cannot_enter_professor_panel(): void
    {
        $panel = Filament::getPanel('professor');

        foreach (['inactive', 'sabbatical', 'terminated'] as $status) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate('teacher', 'web'));
            Teacher::factory()->create(['user_id' => $user->id, 'status' => $status]);

            $this->assertFalse($user->fresh()->canAccessPanel($panel), $status);
        }

        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        Teacher::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'termination_date' => today()->subDay()->toDateString(),
        ]);
        $this->assertFalse($user->fresh()->canAccessPanel($panel));
    }

    public function test_crossed_teacher_assignments_do_not_expand_the_class_subject_scope(): void
    {
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $classA = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $classB = SchoolClass::factory()->create(['school_year_id' => $year->id, 'name' => '5° ANO B']);
        $math = $this->subject('MAT-X', 'Matemática');
        $history = $this->subject('HIS-X', 'História');
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();

        $this->assign($teacherA, $classA, $math);
        $this->assign($teacherA, $classB, $history);
        $this->assign($teacherB, $classA, $history);

        $own = Assessment::factory()->create(['teacher_id' => $teacherA->id, 'class_id' => $classA->id, 'subject_id' => $math->id, 'school_year_id' => $year->id]);
        $crossed = Assessment::factory()->create(['teacher_id' => $teacherA->id, 'class_id' => $classA->id, 'subject_id' => $history->id, 'school_year_id' => $year->id]);
        $otherTeacher = Assessment::factory()->create(['teacher_id' => $teacherB->id, 'class_id' => $classA->id, 'subject_id' => $history->id, 'school_year_id' => $year->id]);

        $service = app(CurrentTeacherService::class);
        $ids = $service->scopeAssignedPairs(
            Assessment::query()->where('teacher_id', $teacherA->id),
            $service->assignments($teacherA),
        )->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertNotContains($crossed->id, $ids);
        $this->assertNotContains($otherTeacher->id, $ids);
    }

    public function test_active_year_is_selected_before_historical_assignments(): void
    {
        $oldYear = SchoolYear::factory()->forYear(now()->year - 1)->create();
        $currentYear = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $oldClass = SchoolClass::factory()->create(['school_year_id' => $oldYear->id]);
        $currentClass = SchoolClass::factory()->create(['school_year_id' => $currentYear->id]);
        $subject = $this->subject('GEO-X', 'Geografia');
        $teacher = Teacher::factory()->create();
        $this->assign($teacher, $oldClass, $subject);
        $this->assign($teacher, $currentClass, $subject);

        $service = app(CurrentTeacherService::class);
        $this->assertSame($currentClass->id, $service->assignments($teacher)->first()->class_id);
        $this->assertSame([$currentClass->id], $service->currentAssignments($teacher)->pluck('class_id')->all());
    }

    public function test_two_assessments_in_same_period_keep_independent_grades(): void
    {
        [$teacher, $class, $subject, $enrollment] = $this->baseContext();
        $first = $this->assessment($teacher, $class, $subject, 'Prova A');
        $second = $this->assessment($teacher, $class, $subject, 'Prova B');
        $recorder = app(RecordTeacherGrades::class);
        $students = collect([['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id]]);

        foreach ([[$first, 7], [$second, 9]] as [$assessment, $score]) {
            $recorder->execute($teacher, $assessment, $students, [
                $enrollment->student_id => ['score' => $score, 'comment' => null],
            ], 10, 'b1', 'test', 1, false, null);
        }

        $this->assertSame(2, Grade::query()->where('student_id', $enrollment->student_id)->count());
        $this->assertEquals(7, Grade::query()->where('assessment_id', $first->id)->value('score'));
        $this->assertEquals(9, Grade::query()->where('assessment_id', $second->id)->value('score'));
    }

    public function test_two_lessons_on_same_day_keep_independent_attendance(): void
    {
        [$teacher, $class, $subject, $enrollment] = $this->baseContext();
        $first = $this->lesson($teacher, $class, $subject, '08:00', '08:50');
        $second = $this->lesson($teacher, $class, $subject, '09:00', '09:50');
        $recorder = app(RecordTeacherAttendance::class);
        $students = collect([['student_id' => $enrollment->student_id]]);

        $recorder->execute($students, $class, $subject, $first, [$enrollment->student_id => ['status' => 'present']], true, true, null);
        $recorder->execute($students, $class, $subject, $second, [$enrollment->student_id => ['status' => 'absent']], true, true, null);

        $this->assertSame(2, Attendance::query()->where('student_id', $enrollment->student_id)->count());
        $this->assertSame('present', Attendance::query()->where('lesson_id', $first->id)->firstOrFail()->status->value);
        $this->assertSame('absent', Attendance::query()->where('lesson_id', $second->id)->firstOrFail()->status->value);
    }

    public function test_attendance_requires_an_explicit_status_instead_of_assuming_presence(): void
    {
        [$teacher, $class, $subject, $enrollment] = $this->baseContext();
        $lesson = $this->lesson($teacher, $class, $subject, '08:00', '08:50');

        try {
            app(RecordTeacherAttendance::class)->execute(
                collect([['student_id' => $enrollment->student_id]]), $class, $subject, $lesson,
                [$enrollment->student_id => ['status' => null]], true, true, null);
            $this->fail('A chamada vazia deveria ser rejeitada.');
        } catch (ValidationException) {
            $this->assertSame(0, Attendance::query()->where('lesson_id', $lesson->id)->count());
            $this->assertFalse($lesson->fresh()->attendance_taken);
        }
    }

    public function test_grade_rejects_student_from_another_class(): void
    {
        [$teacher, $class, $subject] = $this->baseContext();
        $assessment = $this->assessment($teacher, $class, $subject, 'Prova');
        $otherClass = SchoolClass::factory()->create(['school_year_id' => $class->school_year_id, 'name' => '5° ANO B']);
        $otherEnrollment = Enrollment::factory()->create(['class_id' => $otherClass->id, 'school_year_id' => $class->school_year_id]);

        $this->expectException(ValidationException::class);
        app(RecordTeacherGrades::class)->execute($teacher, $assessment,
            collect([['student_id' => $otherEnrollment->student_id, 'enrollment_id' => $otherEnrollment->id]]),
            [$otherEnrollment->student_id => ['score' => 8, 'comment' => null]], 10, 'b1', 'test', 1, false, null);
    }

    public function test_transferred_student_retains_recorded_grade_and_attendance_in_historical_rosters(): void
    {
        [$teacher, $class, $subject, $enrollment] = $this->baseContext();
        $assessment = $this->assessment($teacher, $class, $subject, 'Avaliação histórica');
        $lesson = $this->lesson($teacher, $class, $subject, '08:00', '08:50');

        app(RecordTeacherGrades::class)->execute($teacher, $assessment,
            collect([['student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id]]),
            [$enrollment->student_id => ['score' => 8, 'comment' => null]], 10, 'b1', 'test', 1, false, null);
        app(RecordTeacherAttendance::class)->execute(
            collect([['student_id' => $enrollment->student_id]]), $class, $subject, $lesson,
            [$enrollment->student_id => ['status' => 'present']], true, true, null);

        $enrollment->update(['status' => EnrollmentStatus::TRANSFERRED_INTERNAL]);
        $roster = app(TeacherRosterService::class);

        $this->assertContains($enrollment->id, $roster->forAssessment($assessment)->pluck('id')->all());
        $this->assertContains($enrollment->id, $roster->forLesson($lesson)->pluck('id')->all());
    }

    private function baseContext(): array
    {
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = $this->subject('PORT-X', 'Português');
        $teacher = Teacher::factory()->create();
        $this->assign($teacher, $class, $subject);
        $student = Student::factory()->create();
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $class->id, 'school_year_id' => $year->id]);

        return [$teacher, $class, $subject, $enrollment];
    }

    private function subject(string $code, string $name): Subject
    {
        return Subject::factory()->create(['code' => $code, 'normalized_code' => strtolower($code), 'name' => $name]);
    }

    private function assign(Teacher $teacher, SchoolClass $class, Subject $subject): void
    {
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
    }

    private function assessment(Teacher $teacher, SchoolClass $class, Subject $subject, string $title): Assessment
    {
        return Assessment::factory()->create([
            'teacher_id' => $teacher->id, 'class_id' => $class->id,
            'subject_id' => $subject->id, 'school_year_id' => $class->school_year_id,
            'title' => $title,
        ]);
    }

    private function lesson(Teacher $teacher, SchoolClass $class, Subject $subject, string $start, string $end): Lesson
    {
        return Lesson::create([
            'teacher_id' => $teacher->id, 'class_id' => $class->id,
            'subject_id' => $subject->id, 'school_year_id' => $class->school_year_id,
            'date' => today()->toDateString(), 'start_time' => $start,
            'end_time' => $end, 'status' => 'scheduled',
        ]);
    }
}
