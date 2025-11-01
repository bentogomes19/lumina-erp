<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Subject;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
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
        return $user->can('SubjectsResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('SubjectsResource:View');
    }

    public function create($user)
    {
        return $user->can('SubjectsResource:Create');
    }

    public function update($user)
    {
        return $user->can('SubjectsResource:Update');
    }

    public function delete($user)
    {
        return $user->can('SubjectsResource:Delete');
    }
}
