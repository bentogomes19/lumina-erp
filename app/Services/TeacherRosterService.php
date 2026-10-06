<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

/** Mantém registros históricos visíveis mesmo após a transferência do aluno. */
class TeacherRosterService {
    private function eligible(Builder $query): Builder {
        return $query->whereIn('status', [
            EnrollmentStatus::ACTIVE->value,
            EnrollmentStatus::SUSPENDED->value,
            EnrollmentStatus::LOCKED->value,
        ]);
    }

    public function forAssessment(Assessment $assessment): Collection {
        return Enrollment::query()
            ->where('class_id', $assessment->class_id)
            ->where(fn (Builder $query) => $this->eligible($query)
                ->orWhereHas('grades', fn (Builder $grades) => $grades->where('assessment_id', $assessment->id)))
            ->with('student')
            ->orderBy('roll_number')
            ->get();
    }

    public function forLesson(Lesson $lesson): Collection {
        $recordedStudentIds = Attendance::query()->where('lesson_id', $lesson->id)->pluck('student_id');

        return Enrollment::query()
            ->where('class_id', $lesson->class_id)
            ->where(fn (Builder $query) => $this->eligible($query)
                ->orWhereIn('student_id', $recordedStudentIds))
            ->with('student')
            ->orderBy('roll_number')
            ->get();
    }
}
