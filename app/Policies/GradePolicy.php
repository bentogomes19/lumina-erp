<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function view(User $u, Grade $g): bool
    {
        if ($u->hasRole('admin')) return true;
        if ($u->hasRole('teacher') && $u->teacher && $g->teacher_id === $u->teacher->id) return true;
        if ($u->hasRole('student') && $u->student && $g->student_id === $u->student->id) return true;
        return false;
    }

    public function update(User $u, Grade $g): bool
    {
        if ($u->hasRole('admin')) return true;
        return $u->hasRole('teacher') && $u->teacher && $g->teacher_id === $u->teacher->id;
    }
}
