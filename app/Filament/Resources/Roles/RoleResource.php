<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RoleResource extends BaseAdminResource
{
    protected static ?string $model = Role::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-shield-check';
    protected static string|null|\UnitEnum $navigationGroup = 'Administração';
    protected static ?string $navigationLabel = 'Perfis de Acesso';
    protected static ?string $pluralModelLabel = 'Perfil de Acesso';
    protected static ?string $modelLabel = 'Perfil de Acesso';

    // Apenas TI (e admin) têm acesso — o BaseAdminResource já garante TI acesso total;
    // aqui negamos explicitamente para Secretaria e Financeiro.
    protected static function viewPermission(): string   { return 'roles.view'; }
    protected static function createPermission(): string { return 'roles.create'; }
    protected static function editPermission(): string   { return 'roles.edit'; }
    protected static function deletePermission(): string { return 'roles.delete'; }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }
}
