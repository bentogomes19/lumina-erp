<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy {

    /**
     * Determina se o usuário pode visualizar a lista de usuários.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->can('system.users.view_any');
    }

    /**
     * Determina se o usuário pode visualizar o cadastro informado.
     *
     * @param User $user
     * @param User $model
     *
     * @return bool
     */
    public function view(User $user, User $model): bool {
        return $user->can('system.users.view');
    }

    /**
     * Determina se o usuário pode criar outro usuário.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool {
        return $user->can('system.users.create');
    }

    /**
     * Determina se o usuário pode atualizar outro usuário.
     *
     * @param User $user
     * @param User $model
     *
     * @return bool
     */
    public function update(User $user, User $model): bool {
        return $user->can('system.users.update');
    }

    /**
     * Regra ERP escolar: não permite excluir usuário vinculado a aluno com matrículas
     * ou a professor com vínculos (teacher_assignments).
     *
     * @param User $user
     * @param User $model
     *
     * @return bool
     */
    public function delete(User $user, User $model): bool {
        if (!$user->can('system.users.delete')) {
            return false;
        }

        $student = $model->student;
        if ($student && $student->enrollments()->exists()) {
            return false;
        }

        $teacher = $model->teacher;
        if ($teacher && $teacher->teacherAssignments()->exists()) {
            return false;
        }

        return true;
    }
}
