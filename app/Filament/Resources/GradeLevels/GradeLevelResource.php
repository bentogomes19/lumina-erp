<?php

namespace App\Filament\Resources\GradeLevels;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\GradeLevels\Pages\CreateGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\EditGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\ListGradeLevels;
use App\Filament\Resources\GradeLevels\RelationManagers\SubjectsRelationManager;
use App\Filament\Resources\GradeLevels\Schemas\GradeLevelForm;
use App\Filament\Resources\GradeLevels\Tables\GradeLevelsTable;
use App\Models\GradeLevel;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GradeLevelResource extends BaseAdminResource {

    protected static ?string $model = GradeLevel::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static string|null|BackedEnum $navigationIcon = 'fas-graduation-cap';
    protected static ?string $slug                          = 'grade-levels';
    protected static ?string $navigationLabel               = 'Séries / Etapas';
    protected static ?string $pluralModelLabel              = 'Séries / Etapas';
    protected static ?string $modelLabel                    = 'Séries / Etapas';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'academic.grade_levels.view_any';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'academic.grade_levels.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'academic.grade_levels.update';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'academic.grade_levels.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return GradeLevelForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return GradeLevelsTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
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
            'index'  => ListGradeLevels::route('/'),
            'create' => CreateGradeLevel::route('/create'),
            'edit'   => EditGradeLevel::route('/{record}/edit'),
        ];
    }
}
