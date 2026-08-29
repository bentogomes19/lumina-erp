<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends BaseAdminResource {

    protected static ?string $model                         = Student::class;
    protected static string|null|BackedEnum $navigationIcon = 'fas-graduation-cap';
    protected static ?int $navigationSort                   = 1;
    protected static ?string $navigationLabel               = 'Alunos';
    protected static ?string $pluralModelLabel              = 'Alunos';
    protected static ?string $modelLabel                    = 'Aluno';
    protected static ?string $recordTitleAttribute          = 'name';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'students.view';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'students.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'students.edit';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'students.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return StudentForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return StudentsTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
            EnrollmentsRelationManager::class,
        ];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit'   => EditStudent::route('/{record}/edit'),
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
