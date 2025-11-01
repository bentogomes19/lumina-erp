<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before($user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('UsersResource:ViewAny');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('UsersResource:View');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('UsersResource:Create');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('UsersResource:Update');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('UsersResource:Delete');
    }
}
