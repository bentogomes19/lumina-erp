<?php

namespace Tests\Feature\Teachers;

use App\Models\Assessment;
use App\Models\Lesson;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_print_only_assigned_assessment_and_lesson(): void
    {
        $year = SchoolYear::factory()->forYear(now()->year)->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $subject = Subject::factory()->create(['code' => 'PDF-PT', 'normalized_code' => 'pdf-pt']);
        $teacher = $this->teacherWithReportPermissions();
        $otherTeacher = $this->teacherWithReportPermissions();

        TeacherAssignment::create(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        $assessment = Assessment::factory()->create([
            'teacher_id' => $teacher->id, 'class_id' => $class->id,
            'subject_id' => $subject->id, 'school_year_id' => $year->id,
        ]);
        $lesson = Lesson::create([
            'teacher_id' => $teacher->id, 'class_id' => $class->id,
            'subject_id' => $subject->id, 'school_year_id' => $year->id,
            'date' => today()->toDateString(), 'start_time' => '08:00',
            'end_time' => '08:50', 'status' => 'scheduled',
        ]);

        $this->actingAs($teacher->user, 'teacher');
        $this->get(route('professor.reports.grades', $assessment))->assertOk();
        $this->get(route('professor.reports.attendance', $lesson))->assertOk();

        $this->actingAs($otherTeacher->user, 'teacher');
        $this->get(route('professor.reports.grades', $assessment))->assertForbidden();
        $this->get(route('professor.reports.attendance', $lesson))->assertForbidden();
    }

    private function teacherWithReportPermissions(): Teacher
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('teacher', 'web'));
        foreach (['teacher.grades.view', 'teacher.attendance.view'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return Teacher::factory()->create(['user_id' => $user->id]);
    }
}
