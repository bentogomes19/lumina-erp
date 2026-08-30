<?php

namespace App\Filament\Resources\Enrollments;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentDocumentsRelationManager;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentLogsRelationManager;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Resources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EnrollmentResource extends BaseAdminResource {

    protected static ?string $model                         = Enrollment::class;
    protected static string|null|BackedEnum $navigationIcon = 'fas-layer-group';
    protected static ?int $navigationSort                   = 5;
    protected static ?string $navigationLabel               = 'Matrículas';
    protected static ?string $pluralModelLabel              = 'Matrículas';
    protected static ?string $modelLabel                    = 'Matrícula';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'academic.enrollments.view_any';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'academic.enrollments.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'academic.enrollments.update';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'academic.enrollments.cancel';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return EnrollmentForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return EnrollmentsTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
            EnrollmentLogsRelationManager::class,
            EnrollmentDocumentsRelationManager::class,
        ];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListEnrollments::route('/'),
            'create' => CreateEnrollment::route('/create'),
            'edit'   => EditEnrollment::route('/{record}/edit'),
        ];
    }
}
