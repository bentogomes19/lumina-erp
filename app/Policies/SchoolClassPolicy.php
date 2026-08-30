<?php

namespace App\Policies;

class SchoolClassPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões de turmas.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'academic.classes';
    }
}
