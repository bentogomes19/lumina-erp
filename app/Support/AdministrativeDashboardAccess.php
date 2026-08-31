<?php

namespace App\Support;

use App\Models\User;

class AdministrativeDashboardAccess {

    private const ACCESS_PENDING_URL = '/lumina/acesso-pendente';
    private const ADMIN_DASHBOARD_URL = '/lumina/dashboard-admin';
    private const STUDENT_DASHBOARD_URL = '/lumina/dashboard-student';
    private const TEACHER_DASHBOARD_URL = '/lumina/dashboard-teacher';

    private const ADMINISTRATIVE_ROLES = [
        'ti',
        'admin',
        'secretaria',
        'financeiro',
    ];

    private const DESTINATION_PRIORITY = [
        'student',
        'teacher',
        'ti',
        'admin',
        'secretaria',
        'financeiro',
    ];

    /**
     * Retorna a URL inicial adequada para o perfil principal do usuário.
     *
     * @param User $user
     *
     * @return string
     */
    public static function destinationFor(User $user): string {
        foreach (self::DESTINATION_PRIORITY as $role) {
            if (!$user->hasRole($role)) {
                continue;
            }

            return match ($role) {
                'student' => self::STUDENT_DASHBOARD_URL,
                'teacher' => self::TEACHER_DASHBOARD_URL,
                default   => self::ADMIN_DASHBOARD_URL,
            };
        }

        return self::ACCESS_PENDING_URL;
    }

    /**
     * Indica se o usuário possui um perfil administrativo reconhecido.
     *
     * @param User|null $user
     *
     * @return bool
     */
    public static function hasAdministrativeRole(?User $user): bool {
        if (!$user) {
            return false;
        }

        foreach (self::ADMINISTRATIVE_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna o rótulo do contexto administrativo do usuário.
     *
     * @param User|null $user
     *
     * @return string
     */
    public static function administrativeLabelFor(?User $user): string {
        if (!$user) {
            return 'Acesso administrativo';
        }

        return match (true) {
            $user->hasRole('ti')          => 'Tecnologia da Informação',
            $user->hasRole('admin')       => 'Administração',
            $user->hasRole('secretaria')  => 'Secretaria Acadêmica',
            $user->hasRole('financeiro')  => 'Financeiro',
            default                       => 'Acesso administrativo',
        };
    }
}
