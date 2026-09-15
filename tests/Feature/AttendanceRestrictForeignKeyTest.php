<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRestrictForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_attendance_cannot_be_force_deleted(): void
    {
        $student = Student::factory()->create();

        Attendance::create([
            'student_id' => $student->getKey(),
            'class_id' => SchoolClass::factory()->create()->getKey(),
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->expectException(QueryException::class);

        $student->forceDelete();
    }

    public function test_school_class_with_attendance_cannot_be_force_deleted(): void
    {
        $schoolClass = SchoolClass::factory()->create();

        Attendance::create([
            'student_id' => Student::factory()->create()->getKey(),
            'class_id' => $schoolClass->getKey(),
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->expectException(QueryException::class);

        $schoolClass->forceDelete();
    }
}
