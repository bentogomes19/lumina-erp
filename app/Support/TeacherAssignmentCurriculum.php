<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Collection;

class TeacherAssignmentCurriculum {

    /**
     * Retorna as disciplinas disponíveis para uma turma conforme a matriz ou exceção autorizada.
     *
     * @param int|null $classId
     * @param bool $includeExceptions
     *
     * @return Collection<int, string>
     */
    public static function subjectOptions(?int $classId, bool $includeExceptions = false): Collection {
        $query = Subject::query()->orderBy('name');

        if (!$classId) {
            return collect();
        }

        if ($includeExceptions) {
            return $query->pluck('name', 'id');
        }

        $subjectIds = self::curricularSubjectIds($classId);

        return $query
            ->whereIn('id', $subjectIds)
            ->pluck('name', 'id');
    }

    /**
     * Retorna os IDs de disciplinas pertencentes à matriz curricular da série da turma.
     *
     * @param int $classId
     *
     * @return Collection<int, int>
     */
    public static function curricularSubjectIds(int $classId): Collection {
        $class = SchoolClass::query()->find($classId);

        if (!$class?->grade_level_id) {
            return collect();
        }

        return $class->gradeLevel()
            ->first()
            ?->subjects()
            ->pluck('subjects.id')
            ?? collect();
    }

    /**
     * Indica se a disciplina faz parte da matriz curricular da série da turma.
     *
     * @param int $classId
     * @param int $subjectId
     *
     * @return bool
     */
    public static function isCurricular(int $classId, int $subjectId): bool {
        return self::curricularSubjectIds($classId)->contains((int) $subjectId);
    }
}
