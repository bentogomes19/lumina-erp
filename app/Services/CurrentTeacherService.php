<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Collection;

class CurrentTeacherService
{
    public function current(): ?Teacher
    {
        return auth()->user()?->teacher;
    }

    public function assignments(?Teacher $teacher = null): Collection
    {
        $teacher ??= $this->current();

        if (! $teacher) {
            return collect();
        }

        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->with(['schoolClass.gradeLevel', 'schoolClass.schoolYear', 'subject'])
            ->get();
    }

    public function classIds(?Teacher $teacher = null): Collection
    {
        return $this->assignments($teacher)
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();
    }

    public function subjectIds(?Teacher $teacher = null): Collection
    {
        return $this->assignments($teacher)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();
    }
}
