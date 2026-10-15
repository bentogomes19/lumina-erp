<?php

namespace Tests\Unit;

use App\Filament\Pages\Teacher\TeacherGrades;
use Tests\TestCase;

class TeacherAssessmentTypeMappingTest extends TestCase
{
    public function test_assessment_types_saved_by_teacher_portal_map_to_grade_types(): void
    {
        $page = new TeacherGrades;
        $map = new \ReflectionMethod($page, 'mapAssessmentType');

        $this->assertSame('work', $map->invoke($page, 'work'));
        $this->assertSame('quiz', $map->invoke($page, 'activity'));
        $this->assertSame('participation', $map->invoke($page, 'seminar'));
        $this->assertSame('project', $map->invoke($page, 'project'));
        $this->assertSame('recovery', $map->invoke($page, 'recovery'));
    }
}
