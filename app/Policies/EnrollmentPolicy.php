<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
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
        return $user->can('EnrollmentsResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('EnrollmentsResource:View');
    }

    public function create($user)
    {
        return $user->can('EnrollmentsResource:Create');
    }

    public function update($user)
    {
        return $user->can('EnrollmentsResource:Update');
    }

    public function delete($user)
    {
        return $user->can('EnrollmentsResource:Delete');
    }
}
