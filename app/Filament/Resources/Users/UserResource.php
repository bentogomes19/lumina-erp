<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use App\Models\User;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends BaseAdminResource {

    protected static ?string $model                         = User::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Administração';
    protected static string|null|BackedEnum $navigationIcon = 'fas-users';
    protected static ?int $navigationSort                   = 1;
    protected static ?string $navigationLabel               = 'Usuários';
    protected static ?string $pluralModelLabel              = 'Usuário';
    protected static ?string $modelLabel                    = 'Usuário';

    /* Secretaria tem apenas leitura; TI/admin têm acesso total (garantido pelo BaseAdminResource) */
    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'system.users.view_any';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'system.users.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'system.users.update';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'system.users.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return UserForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return UsersTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Retorna a consulta usada para carregar os registros.
     *
     * @return Builder
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Retorna os widgets registrados na página.
     *
     * @return array
     */
    public static function getWidgets(): array {
        return [UsersOverview::class];
    }

    /**
     * Retorna o indicador numérico exibido na navegação.
     *
     * @return string|null
     */
    public static function getNavigationBadge(): ?string {
        return (string) static::getModel()::count();
    }
}
