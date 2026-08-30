<?php

namespace App\Filament\Pages\Teacher\Concerns;

use App\Support\PermissionAccess;

trait HasTeacherPortalAccess {

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return static::canAccess();
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        $user = auth()->user();

        return PermissionAccess::can(static::teacherPortalPermission())
            && (bool) $user?->teacher?->canAccessOperationally();
    }

    /**
     * Retorna a permissão exigida pelo portal do professor.
     *
     * @return string
     */
    protected static function teacherPortalPermission(): string {
        return static::$teacherPortalPermission ?? 'teacher.dashboard.view';
    }
}
