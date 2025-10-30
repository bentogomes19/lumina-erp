<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SchoolClass;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SchoolClass');
    }

    public function view(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('View:SchoolClass');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SchoolClass');
    }

    public function update(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('Update:SchoolClass');
    }

    public function delete(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('Delete:SchoolClass');
    }

    public function restore(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('Restore:SchoolClass');
    }

    public function forceDelete(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('ForceDelete:SchoolClass');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SchoolClass');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SchoolClass');
    }

    public function replicate(AuthUser $authUser, SchoolClass $schoolClass): bool
    {
        return $authUser->can('Replicate:SchoolClass');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SchoolClass');
    }

}