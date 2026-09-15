<?php

namespace Tests\Unit\Services;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\SchoolYear;
use App\Models\SchoolYearTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\Domain\DeletionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_reports_all_links_and_allows_an_unlinked_teacher(): void
    {
        [$year, $class, $subject, $teacher] = $this->academicContext();

        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);
        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'school_year_id' => $year->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
        $lesson->delete();
        Assessment::factory()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'school_year_id' => $year->id,
        ]);

        $guard = new DeletionGuard;

        $this->assertSame(
            ['1 atribuição', '1 aula', '1 avaliação'],
            $guard->blockingLinks($teacher)->all(),
        );
        $this->assertTrue($guard->blockingLinks(Teacher::factory()->create())->isEmpty());
    }

    public function test_student_reports_all_links_and_allows_an_unlinked_student(): void
    {
        [$year, $class, $subject, $teacher, $student] = $this->academicContext(withStudent: true);
        $enrollment = $this->enroll($student, $class, $year);
        $enrollment->delete();
        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $guard = new DeletionGuard;

        $this->assertSame(
            ['1 matrícula', '1 nota lançada', '1 registro de frequência'],
            $guard->blockingLinks($student)->all(),
        );
        $this->assertTrue($guard->blockingLinks(Student::factory()->create())->isEmpty());
    }

    public function test_subject_reports_all_links_and_allows_an_unlinked_subject(): void
    {
        [$year, $class, $subject, $teacher, $student] = $this->academicContext(withStudent: true);
        $subject->gradeLevels()->attach($class->grade_level_id);
        $class->subjects()->attach($subject->id);
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);
        $assessment = Assessment::factory()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'school_year_id' => $year->id,
        ]);
        $enrollment = $this->enroll($student, $class, $year);
        Grade::factory()->forAssessment($assessment, $enrollment)->create();
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $guard = new DeletionGuard;

        $this->assertSame([
            '1 nível/série',
            '1 turma',
            '1 atribuição',
            '1 avaliação',
            '1 nota lançada',
            '1 registro de frequência',
        ], $guard->blockingLinks($subject)->all());
        $this->assertTrue($guard->blockingLinks(Subject::factory()->create())->isEmpty());
    }

    public function test_school_class_reports_all_links_and_allows_an_unlinked_class(): void
    {
        [$year, $class, $subject, $teacher, $student] = $this->academicContext(withStudent: true);
        $enrollment = $this->enroll($student, $class, $year);
        $class->subjects()->attach($subject->id);
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);
        $assessment = Assessment::factory()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'school_year_id' => $year->id,
        ]);
        Grade::factory()->forAssessment($assessment, $enrollment)->create();
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $guard = new DeletionGuard;

        $this->assertSame([
            '1 matrícula',
            '1 aluno',
            '1 atribuição',
            '1 disciplina',
            '1 nota lançada',
            '1 registro de frequência',
            '1 avaliação',
        ], $guard->blockingLinks($class)->all());
        $this->assertTrue($guard->blockingLinks(SchoolClass::factory()->create())->isEmpty());
    }

    public function test_school_year_reports_all_links_and_allows_an_unlinked_year(): void
    {
        [$year, $class, , , $student] = $this->academicContext(withStudent: true);
        $this->enroll($student, $class, $year);
        SchoolYearTerm::create([
            'school_year_id' => $year->id,
            'name' => '1º Bimestre',
            'type' => 'bimestre',
            'sequence' => 1,
            'starts_at' => "{$year->year}-02-01",
            'ends_at' => "{$year->year}-04-30",
        ]);
        SchoolHoliday::create([
            'school_year_id' => $year->id,
            'name' => 'Recesso',
            'start_date' => "{$year->year}-07-01",
            'end_date' => "{$year->year}-07-10",
            'type' => 'school_recess',
        ]);
        $class->delete();

        $guard = new DeletionGuard;

        $this->assertSame(
            ['1 período letivo', '1 turma', '1 matrícula', '1 feriado/recesso'],
            $guard->blockingLinks($year)->all(),
        );
        $freeYear = SchoolYear::factory()->forYear(now()->year + 1)->create();
        $this->assertTrue($guard->blockingLinks($freeYear)->isEmpty());
    }

    public function test_enrollment_reports_logs_grades_and_documents_and_allows_an_unlinked_enrollment(): void
    {
        [$year, $class, $subject, $teacher, $student] = $this->academicContext(withStudent: true);
        $enrollment = $this->enroll($student, $class, $year);
        Grade::factory()->create([
            'enrollment_id' => $enrollment->getKey(),
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'subject_id' => $subject->getKey(),
            'teacher_id' => $teacher->getKey(),
        ]);
        EnrollmentDocument::create([
            'enrollment_id' => $enrollment->getKey(),
            'tipo' => 'rg',
            'status' => 'entregue',
        ]);

        $guard = new DeletionGuard;

        $this->assertSame(
            ['1 log de auditoria', '1 nota lançada', '1 documento'],
            $guard->blockingLinks($enrollment)->all(),
        );

        $freeEnrollment = $this->enroll(Student::factory()->create(), $class, $year);
        $freeEnrollment->logs()->delete();
        $this->assertTrue($guard->blockingLinks($freeEnrollment)->isEmpty());
    }

    /**
     * @return array{0: SchoolYear, 1: SchoolClass, 2: Subject, 3: Teacher, 4?: Student}
     */
    private function academicContext(bool $withStudent = false): array
    {
        $year = SchoolYear::factory()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $teacher = Teacher::factory()->create();

        $context = [$year, $class, $subject, $teacher];

        if ($withStudent) {
            $context[] = Student::factory()->create();
        }

        return $context;
    }

    private function enroll(Student $student, SchoolClass $class, SchoolYear $year): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $year->id,
        ]);
    }
}
