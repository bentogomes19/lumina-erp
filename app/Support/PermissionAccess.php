<?php

namespace App\Support;

class PermissionAccess {

    /**
     * Determina se o usuário atual possui a permissão informada.
     *
     * @param string $permission
     *
     * @return bool
     */
    public static function can(string $permission): bool {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        /* Permissoes dos portais representam a identidade com que o usuario esta operando, nao privilegios administrativos. Um administrador pode gerenciar alunos e professores sem assumir o portal pessoal deles. */
        $permission         = PermissionCatalog::canonical($permission);
        $requiredPortalRole = self::requiredPortalRole($permission);

        if ($requiredPortalRole && !$user->hasRole($requiredPortalRole)) {
            return false;
        }

        return PermissionCatalog::contains($permission) && $user->can($permission);
    }

    /**
     * Retorna o perfil exigido para acessar o portal atual.
     *
     * @param string $permission
     *
     * @return string|null
     */
    private static function requiredPortalRole(string $permission): ?string {
        return match (true) {
            str_starts_with($permission, 'student.') => 'student',
            str_starts_with($permission, 'teacher.') => 'teacher',
            default                                  => null,
        };
    }

}
