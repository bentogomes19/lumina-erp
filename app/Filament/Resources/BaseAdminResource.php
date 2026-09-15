<?php

namespace App\Filament\Resources;

use App\Support\PermissionCatalog;
use Filament\Resources\Resource;

abstract class BaseAdminResource extends Resource {

    /**
     * Exibe no menu a quantidade atual de registros do módulo.
     * Recursos com SoftDeletes contam somente registros ativos, como nas listagens.
     */
    public static function getNavigationBadge(): ?string {
        return (string) static::getModel()::count();
    }

    /**
     * Mantém o contador com a mesma linguagem visual usada em Disciplinas.
     */
    public static function getNavigationBadgeColor(): ?string {
        return 'primary';
    }

    /**
     * Determina se o usuário possui a permissão canônica informada.
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

        return PermissionCatalog::contains($permission) && $user->can($permission);
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

    /* Permissões canônicas definidas pelas subclasses. */
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
