<?php

namespace App\Support;

use App\Models\User;

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

        return $user instanceof User && self::for($user, $permission);
    }

    /**
     * Determina se um usuário possui a permissão informada.
     *
     * Esta é a entrada usada por Policies e componentes que já possuem o
     * usuário em mãos, sem depender do contexto global de autenticação.
     *
     * @param User $user
     * @param string $permission
     *
     * @return bool
     */
    public static function for(User $user, string $permission): bool {

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
