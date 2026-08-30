<?php

namespace App\Policies;

class RolePolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões de perfis de acesso.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'system.roles';
    }
}
