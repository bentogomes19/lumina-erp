<?php

namespace App\Filament\Resources\SchoolClasses;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\SchoolClasses\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\SchoolClasses\RelationManagers\SubjectsRelationManager;
use App\Filament\Resources\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Resources\SchoolClasses\Tables\SchoolClassesTable;
use App\Models\SchoolClass;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolClassResource extends BaseAdminResource
{
    protected static ?string $model = SchoolClass::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Turmas';
    protected static ?string $pluralModelLabel = 'Turmas';
    protected static ?string $modelLabel = 'Turmas';

    protected static function viewPermission(): string   { return 'classes.view'; }
    protected static function createPermission(): string { return 'classes.create'; }
    protected static function editPermission(): string   { return 'classes.edit'; }
    protected static function deletePermission(): string { return 'classes.delete'; }

    public static function form(Schema $schema): Schema
    {
        return SchoolClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
            SubjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit'   => EditSchoolClass::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
