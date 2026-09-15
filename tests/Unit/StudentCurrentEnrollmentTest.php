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
}
