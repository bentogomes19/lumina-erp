<?php

namespace App\Policies;

class TeacherAssignmentPolicy extends CanonicalResourcePolicy {

    /**
     * Retorna o prefixo canônico das permissões de alocação docente.
     *
     * @return string
     */
    protected function permissionPrefix(): string {
        return 'admin.teachers.assignments';
    }
}
