<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Builder;
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

        $currentYearId = SchoolYear::current()?->id;

        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->with(['schoolClass.gradeLevel', 'schoolClass.schoolYear', 'subject'])
            ->get()
            ->sortBy([
                fn (TeacherAssignment $a, TeacherAssignment $b) => (int) ($b->schoolClass?->school_year_id === $currentYearId)
                    <=> (int) ($a->schoolClass?->school_year_id === $currentYearId),
                fn (TeacherAssignment $a, TeacherAssignment $b) => ($b->schoolClass?->schoolYear?->year ?? 0)
                    <=> ($a->schoolClass?->schoolYear?->year ?? 0),
            ])->values();
    }

    /** Vínculos do ano letivo ativo, sem misturar turmas históricas. */
    public function currentAssignments(?Teacher $teacher = null): Collection {
        $yearId = SchoolYear::current()?->id;

        return $yearId
            ? $this->assignments($teacher)->filter(fn (TeacherAssignment $assignment) =>
                (int) $assignment->schoolClass?->school_year_id === (int) $yearId
            )->values()
            : collect();
    }

    /** Restringe uma consulta aos pares turma–disciplina realmente atribuídos. */
    public function scopeAssignedPairs(Builder $query, Collection $assignments): Builder {
        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $pairs) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $pairs->orWhere(function (Builder $pair) use ($assignment): void {
                    $pair->where('class_id', $assignment->class_id)
                        ->where('subject_id', $assignment->subject_id);
                });
            }
        });
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
