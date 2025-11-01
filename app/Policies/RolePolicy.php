<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
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
        return $user->can('RolesResource:ViewAny');
    }

    public function view($user)
    {
        return $user->can('RolesResource:View');
    }

    public function create($user)
    {
        return $user->can('RolesResource:Create');
    }

    public function update($user)
    {
        return $user->can('RolesResource:Update');
    }

    public function delete($user)
    {
        return $user->can('RolesResource:Delete');
    }
}
