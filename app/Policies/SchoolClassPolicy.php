<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SchoolClass;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
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
        return $user->can('SchoolClassesResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('SchoolClassesResource:View');
    }

    public function create($user)
    {
        return $user->can('SchoolClassesResource:Create');
    }

    public function update($user)
    {
        return $user->can('SchoolClassesResource:Update');
    }

    public function delete($user)
    {
        return $user->can('SchoolClassesResource:Delete');
    }
}
