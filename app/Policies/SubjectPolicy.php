<?php

namespace App\Policies;

class SubjectPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões administrativas de disciplinas.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'academic.subjects';
    }
}
