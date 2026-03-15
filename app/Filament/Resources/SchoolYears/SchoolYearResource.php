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

class SchoolYearResource extends BaseAdminResource
{
    protected static ?string $model = SchoolYear::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static ?string $slug = 'school-years';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Ano Letivo';
    protected static ?string $pluralModelLabel = 'Ano Letivo';
    protected static ?string $modelLabel = 'Ano Letivo';

    protected static function viewPermission(): string   { return 'school_years.view'; }
    protected static function createPermission(): string { return 'school_years.create'; }
    protected static function editPermission(): string   { return 'school_years.edit'; }
    protected static function deletePermission(): string { return 'school_years.delete'; }

    public static function form(Schema $schema): Schema
    {
        return SchoolYearForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolYearsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TermsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSchoolYears::route('/'),
            'create' => CreateSchoolYear::route('/create'),
            'edit'   => EditSchoolYear::route('/{record}/edit'),
        ];
    }
}
