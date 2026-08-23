<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy {

    /**
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     *
     *
     * @param User $user
     * @param Enrollment $enrollment
     * @return bool
     */
    public function view(User $user, Enrollment $enrollment): bool {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool {
        return $user->hasRole('admin');
    }

    public function update(User $user, Enrollment $enrollment): bool {
        return $user->hasRole('admin');
    }

    /**
     * Regra: não permite excluir matrícula que possua notas (grades) lançadas.
     * Orienta-se cancelar a matrícula em vez de excluir o registro.
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
