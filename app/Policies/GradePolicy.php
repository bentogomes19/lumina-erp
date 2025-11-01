<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Grade;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradePolicy
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
        return $user->can('GradesResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('GradesResource:View');
    }

    public function create($user)
    {
        return $user->can('GradesResource:Create');
    }

    public function update($user)
    {
        return $user->can('GradesResource:Update');
    }

    public function delete($user)
    {
        return $user->can('GradesResource:Delete');
    }
}
