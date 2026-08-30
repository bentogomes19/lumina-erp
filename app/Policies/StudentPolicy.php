<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy {

    /**
     * Determina se o usuário pode visualizar a lista de alunos.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->can('academic.students.view_any');
    }

    /**
     * Determina se o usuário pode visualizar o aluno informado.
     *
     * @param User $user
     * @param Student $student
     *
     * @return bool
     */
    public function view(User $user, Student $student): bool {
        return $user->can('academic.students.view');
    }

    /**
     * Determina se o usuário pode criar um aluno.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool {
        return $user->can('academic.students.create');
    }

    /**
     * Determina se o usuário pode atualizar o aluno.
     *
     * @param User $user
     * @param Student $student
     *
     * @return bool
     */
    public function update(User $user, Student $student): bool {
        return $user->can('academic.students.update');
    }

    /**
     * Regra ERP escolar: não permite excluir aluno vinculado a matrículas/turmas.
     *
     * @param User $user
     * @param Student $student
     *
     * @return bool
     */
    public function delete(User $user, Student $student): bool {
        if (!$user->can('academic.students.delete')) {
            return false;
        }

        if ($student->enrollments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determina se o aluno pode ser excluído definitivamente.
     *
     * @param User $user
     * @param Student $student
     *
     * @return bool
     */
    public function forceDelete(User $user, Student $student): bool {
        return $this->delete($user, $student);
    }

    /**
     * Determina se o usuário pode restaurar o aluno excluído.
     *
     * @param User $user
     * @param Student $student
     *
     * @return bool
     */
    public function restore(User $user, Student $student): bool {
        return $user->can('academic.students.update');
    }
}
