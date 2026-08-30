<?php

namespace App\Policies;

class TeacherPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões administrativas de professores.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'admin.teachers';
    }
}
