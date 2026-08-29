<?php

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Resources\Subjects\Schemas\SubjectForm;
use App\Filament\Resources\Subjects\Tables\SubjectsTable;
use App\Models\Subject;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubjectResource extends BaseAdminResource {

    protected static ?string $model                         = Subject::class;
    protected static string|null|BackedEnum $navigationIcon = 'fas-book-open';
    protected static ?int $navigationSort                   = 3;
    protected static ?string $navigationLabel               = 'Disciplinas';
    protected static ?string $recordTitleAttribute          = 'name';
    protected static ?string $pluralModelLabel              = 'Disciplinas';
    protected static ?string $modelLabel                    = 'Disciplina';

    /**
     * Retorna o nome da permissão necessária para visualizar o recurso.
     *
     * @return string
     */
    protected static function viewPermission(): string {
        return 'subjects.view';
    }
    /**
     * Retorna a permissão necessária para criar registros do recurso.
     *
     * @return string
     */
    protected static function createPermission(): string {
        return 'subjects.create';
    }
    /**
     * Retorna o nome da permissão necessária para editar o recurso.
     *
     * @return string
     */
    protected static function editPermission(): string {
        return 'subjects.edit';
    }
    /**
     * Retorna a permissão necessária para excluir registros do recurso.
     *
     * @return string
     */
    protected static function deletePermission(): string {
        return 'subjects.delete';
    }

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function form(Schema $schema): Schema {
        return SubjectForm::configure($schema);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table {
        return SubjectsTable::configure($table);
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
            'index'  => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit'   => EditSubject::route('/{record}/edit'),
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
     * Retorna os atributos disponíveis na pesquisa global.
     *
     * @return array
     */
    public static function getGloballySearchableAttributes(): array {
        return ['name', 'code', 'bncc_code'];
    }

    /**
     * Retorna o indicador numérico exibido na navegação.
     *
     * @return string|null
     */
    public static function getNavigationBadge(): ?string {
        return (string) Subject::count();
    }

    /**
     * Retorna a cor do indicador numérico exibido na navegação.
     *
     * @return string|null
     */
    public static function getNavigationBadgeColor(): ?string {
        return 'primary';
    }
}
