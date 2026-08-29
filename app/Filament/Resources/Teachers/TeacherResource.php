<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Teachers\RelationManager\AssignmentsRelationManager;
use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\Tables\TeachersTable;
use App\Models\Teacher;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherResource extends BaseAdminResource {

    protected static ?string $model                         = Teacher::class;
    protected static string|null|BackedEnum $navigationIcon = 'fas-user-group';
    protected static ?int $navigationSort                   = 2;

    protected static ?string $navigationLabel      = 'Professores';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $pluralModelLabel     = 'Professor';
    protected static ?string $modelLabel           = 'Professor';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'teachers.view';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'teachers.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'teachers.edit';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'teachers.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return TeacherForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return TeachersTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit'   => EditTeacher::route('/{record}/edit'),
        ];
    }

    /**
     * Retorna a consulta usada para carregar os registros.
     *
     * @return Builder
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


}
