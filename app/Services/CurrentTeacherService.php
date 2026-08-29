<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Collection;

class CurrentTeacherService {

    /**
     * Retorna o professor correspondente ao usuário autenticado.
     *
     * @return Teacher|null
     */
    public function current(): ?Teacher {
        return auth()->user()?->teacher;
    }

    /**
     * Retorna as atribuições do professor autenticado.
     *
     * @param Teacher|null $teacher
     *
     * @return Collection
     */
    public function assignments(?Teacher $teacher = null): Collection {
        $teacher ??= $this->current();

        if (!$teacher) {
            return collect();
        }

        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->with(['schoolClass.gradeLevel', 'schoolClass.schoolYear', 'subject'])
            ->get();
    }

    /**
     * Retorna os identificadores das turmas atribuídas ao professor.
     *
     * @param Teacher|null $teacher
     *
     * @return Collection
     */
    public function classIds(?Teacher $teacher = null): Collection {
        return $this->assignments($teacher)
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Retorna os identificadores das disciplinas atribuídas ao professor.
     *
     * @param Teacher|null $teacher
     *
     * @return Collection
     */
    public function subjectIds(?Teacher $teacher = null): Collection {
        return $this->assignments($teacher)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();
    }
}
