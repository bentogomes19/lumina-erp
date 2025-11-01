<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Teacher;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    public function before($user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny($user)
    {
        return $user->can('TeachersResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('TeachersResource:View');
    }

    public function create($user)
    {
        return $user->can('TeachersResource:Create');
    }

    public function update($user)
    {
        return $user->can('TeachersResource:Update');
    }

    public function delete($user)
    {
        return $user->can('TeachersResource:Delete');
    }
}
