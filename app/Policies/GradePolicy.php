<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy {

    /**
     * Determina se o usuário pode visualizar a nota informada.
     *
     * @param User $user
     * @param Grade $grade
     *
     * @return bool
     */
    public function view(User $user, Grade $grade): bool {
        if ($user->can('academic.grades.view')) {
            return true;
        }

        if ($user->hasRole('teacher')
            && $user->can('teacher.grades.view')
            && $user->teacher
            && $grade->teacher_id === $user->teacher->id) {
            return true;
        }

        if ($user->hasRole('student')
            && $user->can('student.grades.view')
            && $user->student
            && $grade->student_id === $user->student->id) {
            return true;
        }

        return false;
    }

    /**
     * Determina se o usuário pode atualizar a nota.
     *
     * @param User $user
     * @param Grade $grade
     *
     * @return bool
     */
    public function update(User $user, Grade $grade): bool {
        if ($user->can('academic.grades.update')) {
            return true;
        }

        return $user->hasRole('teacher')
            && $user->can('teacher.grades.update')
            && $user->teacher
            && $grade->teacher_id === $user->teacher->id;
    }
}
