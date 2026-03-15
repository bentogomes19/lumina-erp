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

class UserResource extends BaseAdminResource
{
    protected static ?string $model = User::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Administração';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Usuários';
    protected static ?string $pluralModelLabel = 'Usuário';
    protected static ?string $modelLabel = 'Usuário';

    // Secretaria tem apenas leitura; TI/admin têm acesso total (garantido pelo BaseAdminResource)
    protected static function viewPermission(): string   { return 'users.view'; }
    protected static function createPermission(): string { return 'users.create'; }
    protected static function editPermission(): string   { return 'users.edit'; }
    protected static function deletePermission(): string { return 'users.delete'; }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getWidgets(): array
    {
        return [UsersOverview::class];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
