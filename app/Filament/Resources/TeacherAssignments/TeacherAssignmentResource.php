<?php

namespace App\Filament\Resources\TeacherAssignments;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\TeacherAssignments\Pages\CreateTeacherAssignment;
use App\Filament\Resources\TeacherAssignments\Pages\EditTeacherAssignment;
use App\Filament\Resources\TeacherAssignments\Pages\ListTeacherAssignments;
use App\Filament\Resources\TeacherAssignments\Schemas\TeacherAssignmentForm;
use App\Filament\Resources\TeacherAssignments\Tables\TeacherAssignmentsTable;
use App\Models\TeacherAssignment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TeacherAssignmentResource extends BaseAdminResource {

    protected static ?string $model = TeacherAssignment::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static string|null|BackedEnum $navigationIcon = 'fas-user-group';
    protected static ?string $navigationLabel               = 'Alocação de Professores';
    protected static ?string $pluralModelLabel              = 'Alocação de Professores';
    protected static ?string $modelLabel                    = 'Alocação do Professor';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'teacher_assignments.view';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'teacher_assignments.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'teacher_assignments.edit';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'teacher_assignments.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return TeacherAssignmentForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return TeacherAssignmentsTable::configure($table);
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
            'index'  => ListTeacherAssignments::route('/'),
            'create' => CreateTeacherAssignment::route('/create'),
            'edit'   => EditTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
