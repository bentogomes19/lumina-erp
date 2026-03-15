<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseAdminResource extends Resource
{
    /** Perfis com acesso ao painel administrativo */
    public const ADMIN_ROLES = ['admin', 'ti', 'secretaria', 'financeiro'];

    /** Retorna true se o usuário autenticado possui qualquer perfil administrativo */
    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole(static::ADMIN_ROLES) ?? false;
    }

    /**
     * Verifica se o usuário tem permissão específica OU pertence a um perfil
     * que deve ter acesso total (ti/admin).
     */
    public static function hasModulePermission(string $permission): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        // TI e admin têm acesso irrestrito
        if ($user->hasAnyRole(['admin', 'ti'])) {
            return true;
        }
        return $user->can($permission);
    }

    public static function canViewAny(): bool
    {
        return static::hasModulePermission(static::viewPermission());
    }

    public static function canCreate(): bool
    {
        return static::hasModulePermission(static::createPermission());
    }

    public static function canEdit($record): bool
    {
        return static::hasModulePermission(static::editPermission());
    }

    public static function canDelete($record): bool
    {
        return static::hasModulePermission(static::deletePermission());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    // Hooks para as subclasses sobrescreverem com a permissão do módulo
    protected static function viewPermission(): string   { return ''; }
    protected static function createPermission(): string { return ''; }
    protected static function editPermission(): string   { return ''; }
    protected static function deletePermission(): string { return ''; }
}
