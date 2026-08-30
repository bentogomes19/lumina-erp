<?php

namespace App\Filament\Resources\SchoolYears;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\SchoolYears\Pages\CreateSchoolYear;
use App\Filament\Resources\SchoolYears\Pages\EditSchoolYear;
use App\Filament\Resources\SchoolYears\Pages\ListSchoolYears;
use App\Filament\Resources\SchoolYears\RelationManagers\TermsRelationManager;
use App\Filament\Resources\SchoolYears\Schemas\SchoolYearForm;
use App\Filament\Resources\SchoolYears\Tables\SchoolYearsTable;
use App\Models\SchoolYear;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SchoolYearResource extends BaseAdminResource {

    protected static ?string $model = SchoolYear::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static ?string $slug                          = 'school-years';
    protected static string|null|BackedEnum $navigationIcon = 'fas-calendar';
    protected static ?string $navigationLabel               = 'Ano Letivo';
    protected static ?string $pluralModelLabel              = 'Ano Letivo';
    protected static ?string $modelLabel                    = 'Ano Letivo';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'academic.school_years.view_any';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'academic.school_years.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'academic.school_years.update';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'academic.school_years.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return SchoolYearForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return SchoolYearsTable::configure($table);
    }

    /**
     * Retorna os gerenciadores de relações do recurso.
     *
     * @return array
     */
    public static function getRelations(): array {
        return [
            TermsRelationManager::class,
        ];
    }

    /**
     * Retorna as páginas registradas no recurso.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'index'  => ListSchoolYears::route('/'),
            'create' => CreateSchoolYear::route('/create'),
            'edit'   => EditSchoolYear::route('/{record}/edit'),
        ];
    }
}
