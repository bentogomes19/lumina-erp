<?php

namespace App\Policies;

use App\Models\User;

class AdminOnlyPolicy {

    /**
     * Determina se o usuário pode visualizar qualquer registro protegido pela política.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode visualizar um registro protegido pela política.
     *
     * @param User $user
     *
     * @return bool
     */
    public function view(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode criar um registro protegido pela política.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode atualizar um registro protegido pela política.
     *
     * @param User $user
     *
     * @return bool
     */
    public function update(User $user): bool {
        return $user->hasRole('admin');
    }

    /**
     * Determina se o usuário pode excluir um registro protegido pela política.
     *
     * @param User $user
     *
     * @return bool
     */
    public function delete(User $user): bool {
        return $user->hasRole('admin');
    }
}
