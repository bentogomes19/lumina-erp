<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy {

    /**
     * Determina se o usuário pode visualizar a lista de matrículas.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode visualizar a matrícula informada.
     *
     * @param User $user
     * @param Enrollment $enrollment
     *
     * @return bool
     */
    public function view(User $user, Enrollment $enrollment): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode emitir documentos da matrícula.
     *
     * @param User $user
     * @param Enrollment $enrollment
     *
     * @return bool
     */
    public function viewDocument(User $user, Enrollment $enrollment): bool {
        if (!$user->active || $user->is_locked) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'ti', 'secretaria', 'financeiro'])) {
            return true;
        }

        return $user->hasRole('student')
            && $user->student()->whereKey($enrollment->student_id)->exists();
    }

    /**
     * Determina se o usuário pode criar uma matrícula.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode atualizar a matrícula.
     *
     * @param User $user
     * @param Enrollment $enrollment
     *
     * @return bool
     */
    public function update(User $user, Enrollment $enrollment): bool {
        return $user->hasRole('admin');
    }

    /**
     * Impede a exclusão de uma matrícula que possua notas lançadas.
     * Orienta-se cancelar a matrícula em vez de excluir o registro.
     *
     * @param User $user
     * @param Enrollment $enrollment
     *
     * @return bool
     */
    public function delete(User $user, Enrollment $enrollment): bool {
        if (!$user->hasRole('admin')) {
            return false;
        }

        if ($enrollment->grades()->exists()) {
            return false;
        }

        return true;
    }
}
