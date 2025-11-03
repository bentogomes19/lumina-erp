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

class TeacherResource extends BaseAdminResource
{
    protected static ?string $model = Teacher::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Professores';
    protected static ?string $recordTitleAttribute = 'Professor';
    protected static ?string $pluralModelLabel = 'Professor';
    protected static ?string $modelLabel = 'Professor';


    public static function form(Schema $schema): Schema
    {
        return TeacherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


}
