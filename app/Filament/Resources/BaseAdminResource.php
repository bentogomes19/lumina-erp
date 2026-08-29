<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseAdminResource extends Resource {

    /** Perfis com acesso ao painel administrativo */
    public const ADMIN_ROLES = ['admin', 'ti', 'secretaria', 'financeiro'];

    /**
     * Determina se o usuário autenticado possui algum perfil administrativo.
     *
     * @return bool
     */
    public static function isAdminUser(): bool {
        return auth()->user()?->hasAnyRole(static::ADMIN_ROLES) ?? false;
    }

    /**
     * Determina se o usuário possui a permissão informada ou acesso administrativo irrestrito.
     *
     * @param string $permission
     *
     * @return bool
     */
    public static function hasModulePermission(string $permission): bool {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        /* TI e admin têm acesso irrestrito. */
        if ($user->hasAnyRole(['admin', 'ti'])) {
            return true;
        }
        return $user->can($permission);
    }

    /**
     * Determina se o usuário pode visualizar os registros do recurso.
     *
     * @return bool
     */
    public static function canViewAny(): bool {
        return static::hasModulePermission(static::viewPermission());
    }

    /**
     * Determina se o usuário pode criar um registro.
     *
     * @return bool
     */
    public static function canCreate(): bool {
        return static::hasModulePermission(static::createPermission());
    }

    /**
     * Determina se o usuário pode editar o registro.
     *
     * @param mixed $record
     *
     * @return bool
     */
    public static function canEdit($record): bool {
        return static::hasModulePermission(static::editPermission());
    }

    /**
     * Determina se o usuário pode excluir o registro.
     *
     * @param mixed $record
     *
     * @return bool
     */
    public static function canDelete($record): bool {
        return static::hasModulePermission(static::deletePermission());
    }

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return static::canViewAny();
    }

    /* Hooks para as subclasses sobrescreverem com a permissão do módulo. */
    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return '';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return '';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return '';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return '';
    }
}
