<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Regra ERP escolar: não permite excluir usuário vinculado a aluno com matrículas
     * ou a professor com vínculos (teacher_assignments).
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->hasRole('admin')) {
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
