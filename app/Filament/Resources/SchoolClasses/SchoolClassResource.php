<?php

namespace App\Filament\Resources\SchoolClasses;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\SchoolClasses\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\SchoolClasses\RelationManagers\SubjectsRelationManager;
use App\Filament\Resources\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Resources\SchoolClasses\Tables\SchoolClassesTable;
use App\Models\SchoolClass;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolClassResource extends BaseAdminResource {

    protected static ?string $model = SchoolClass::class;

    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-group';
    protected static ?string $navigationLabel                = 'Turmas';
    protected static ?string $pluralModelLabel               = 'Turmas';
    protected static ?string $modelLabel                     = 'Turmas';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'academic.classes.view_any';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'academic.classes.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'academic.classes.update';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'academic.classes.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return SchoolClassForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return SchoolClassesTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
            StudentsRelationManager::class,
            SubjectsRelationManager::class,
        ];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit'   => EditSchoolClass::route('/{record}/edit'),
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
}
