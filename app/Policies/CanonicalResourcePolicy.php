<?php

namespace App\Policies;

use App\Models\User;

abstract class CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões protegidas pela política.
     *
     * @return string
     */
    abstract protected function permissionPrefix(): string;

    /**
     * Determina se o usuário pode listar os registros.
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool {
        return $user->can($this->permission('view_any'));
    }

    /**
     * Determina se o usuário pode visualizar o registro.
     *
     * @param User $user
     * @param mixed $record
     *
     * @return bool
     */
    public function view(User $user, mixed $record): bool {
        return $user->can($this->permission('view'));
    }

    /**
     * Determina se o usuário pode criar registros.
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool {
        return $user->can($this->permission('create'));
    }

    /**
     * Determina se o usuário pode atualizar o registro.
     *
     * @param User $user
     * @param mixed $record
     *
     * @return bool
     */
    public function update(User $user, mixed $record): bool {
        return $user->can($this->permission('update'));
    }

    /**
     * Determina se o usuário pode excluir o registro.
     *
     * @param User $user
     * @param mixed $record
     *
     * @return bool
     */
    public function delete(User $user, mixed $record): bool {
        return $user->can($this->permission('delete'));
    }

    /**
     * Monta o nome canônico da permissão para a ação informada.
     *
     * @param string $action
     *
     * @return string
     */
    private function permission(string $action): string {
        return "{$this->permissionPrefix()}.{$action}";
    }
}
