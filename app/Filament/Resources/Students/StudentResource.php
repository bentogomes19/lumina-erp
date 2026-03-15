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

class StudentResource extends BaseAdminResource
{
    protected static ?string $model = Student::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Alunos';
    protected static ?string $pluralModelLabel = 'Alunos';
    protected static ?string $modelLabel = 'Aluno';
    protected static ?string $recordTitleAttribute = 'name';

    protected static function viewPermission(): string   { return 'students.view'; }
    protected static function createPermission(): string { return 'students.create'; }
    protected static function editPermission(): string   { return 'students.edit'; }
    protected static function deletePermission(): string { return 'students.delete'; }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit'   => EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
