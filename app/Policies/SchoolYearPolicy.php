<?php

namespace App\Policies;

class SchoolYearPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões de anos letivos.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'academic.school_years';
    }
}
