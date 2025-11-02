<?php

namespace App\Policies;

use App\Models\User;

class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
