<?php

namespace Tests\Unit;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCurrentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_active_enrollment_returns_only_the_current_school_year_class(): void
    {
        $currentYear = SchoolYear::factory()->active()->create();
        $previousYear = SchoolYear::factory()->forYear(now()->year - 1)->create();
        $currentClass = SchoolClass::factory()->create([
            'name' => '7º Ano A - Atual',
            'school_year_id' => $currentYear->id,
        ]);
        $previousClass = SchoolClass::factory()->create([
            'name' => '6º Ano A - Histórico',
            'school_year_id' => $previousYear->id,
        ]);
        $student = Student::factory()->create();

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $previousClass->id,
            'school_year_id' => $previousYear->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrollment_date' => $previousYear->starts_at,
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $currentClass->id,
            'school_year_id' => $currentYear->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrollment_date' => $currentYear->starts_at,
        ]);

        $student->load('currentActiveEnrollment.schoolClass');

        $this->assertSame('7º Ano A - Atual', $student->currentActiveEnrollment?->schoolClass?->name);
        $this->assertNotSame('6º Ano A - Histórico', $student->currentActiveEnrollment?->schoolClass?->name);
    }

    public function test_current_class_ignores_transferred_enrollment_in_the_same_year(): void
    {
        $year = SchoolYear::factory()->active()->create();
        $originalClass = SchoolClass::factory()->create([
            'name' => '5º Ano A - Origem',
            'school_year_id' => $year->id,
        ]);
        $newClass = SchoolClass::factory()->create([
            'name' => '5º Ano B - Destino',
            'school_year_id' => $year->id,
        ]);
        $student = Student::factory()->create();

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $originalClass->id,
            'school_year_id' => $year->id,
            'status' => EnrollmentStatus::TRANSFERRED_INTERNAL,
            'enrollment_date' => $year->starts_at,
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $newClass->id,
            'school_year_id' => $year->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrollment_date' => $year->starts_at,
        ]);

        $this->assertSame($newClass->id, $student->currentClass()?->id);
    }
}
