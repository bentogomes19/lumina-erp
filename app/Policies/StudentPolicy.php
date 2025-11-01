<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
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
        return $user->can('StudentsResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('StudentsResource:View');
    }

    public function create($user)
    {
        return $user->can('StudentsResource:Create');
    }

    public function update($user)
    {
        return $user->can('StudentsResource:Update');
    }

    public function delete($user)
    {
        return $user->can('StudentsResource:Delete');
    }
}
