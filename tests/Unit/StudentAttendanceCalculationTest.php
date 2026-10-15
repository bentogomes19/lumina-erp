<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Filament\Pages\Student\StudentAttendance;
use App\Models\Attendance;
use App\Models\Subject;
use Tests\TestCase;

class StudentAttendanceCalculationTest extends TestCase
{
    public function test_justified_absence_does_not_count_as_presence_in_overall_or_subject_rate(): void
    {
        $subject = new Subject(['name' => 'Português']);
        $records = collect([
            AttendanceStatus::PRESENT,
            AttendanceStatus::LATE,
            AttendanceStatus::ABSENT,
            AttendanceStatus::EXCUSED,
        ])->map(function (AttendanceStatus $status) use ($subject) {
            $record = new Attendance(['status' => $status, 'subject_id' => 1]);
            $record->setRelation('subject', $subject);

            return $record;
        });

        $page = new StudentAttendance;
        $overall = (new \ReflectionMethod($page, 'computeStats'))->invoke($page, $records, 75.0);
        $bySubject = (new \ReflectionMethod($page, 'computeSubjectStats'))->invoke($page, $records, 75.0);

        $this->assertSame(50.0, $overall['rate']);
        $this->assertSame(-1, $overall['remaining_absences']);
        $this->assertTrue($overall['alert']);
        $this->assertSame(50.0, $bySubject[0]['rate']);
        $this->assertSame(-1, $bySubject[0]['remaining_absences']);
    }
}
