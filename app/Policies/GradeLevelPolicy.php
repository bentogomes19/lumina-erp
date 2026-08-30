<?php

namespace App\Policies;

class GradeLevelPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões de séries e etapas.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'academic.grade_levels';
    }
}
