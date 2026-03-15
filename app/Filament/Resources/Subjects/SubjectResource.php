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

class SubjectResource extends BaseAdminResource
{
    protected static ?string $model = Subject::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Disciplinas';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $pluralModelLabel = 'Disciplinas';
    protected static ?string $modelLabel = 'Disciplina';

    protected static function viewPermission(): string   { return 'subjects.view'; }
    protected static function createPermission(): string { return 'subjects.create'; }
    protected static function editPermission(): string   { return 'subjects.edit'; }
    protected static function deletePermission(): string { return 'subjects.delete'; }

    public static function form(Schema $schema): Schema
    {
        return SubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit'   => EditSubject::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'bncc_code'];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Subject::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
