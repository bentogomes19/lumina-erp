<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Regra ERP escolar: não permite excluir aluno vinculado a matrículas/turmas.
     */
    public function delete(User $user, Student $student): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        if ($student->enrollments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Force delete segue a mesma regra: só se não tiver matrículas.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return $this->delete($user, $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }
}
