<?php

namespace App\Policies;

use App\Models\User;

class AdminOnlyPolicy {

    /**
     *
     *
     * @param  User $user
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     *
     *
     * @param  User $user
     * @return bool
     */
    public function view(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     *
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     *
     *
     * @param  User $user
     * @return bool
     */
    public function update(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     *
     *
     * @param  User $user
     * @return bool
     */
    public function delete(User $user): bool {
        return $user->hasRole('admin');
    }
}
