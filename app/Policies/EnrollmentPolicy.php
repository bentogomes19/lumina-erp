<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode emitir documentos da matrícula.
     */
    public function viewDocument(User $user, Enrollment $enrollment): bool
    {
        if (! $user->active || $user->is_locked) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'ti', 'secretaria', 'financeiro'])) {
            return true;
        }

        return $user->hasRole('student')
            && $user->student()->whereKey($enrollment->student_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Regra: não permite excluir matrícula que possua notas (grades) lançadas.
     * Orienta-se cancelar a matrícula em vez de excluir o registro.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        if ($enrollment->grades()->exists()) {
            return false;
        }

        return true;
    }
}
