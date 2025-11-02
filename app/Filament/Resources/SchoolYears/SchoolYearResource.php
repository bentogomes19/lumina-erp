<?php

namespace App\Filament\Resources\SchoolYears;

use App\Filament\Resources\SchoolYears\Pages\CreateSchoolYear;
use App\Filament\Resources\SchoolYears\Pages\EditSchoolYear;
use App\Filament\Resources\SchoolYears\Pages\ListSchoolYears;
use App\Filament\Resources\SchoolYears\Schemas\SchoolYearForm;
use App\Filament\Resources\SchoolYears\Tables\SchoolYearsTable;
use App\Models\SchoolYear;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SchoolYearResource extends Resource
{
    protected static ?string $model = SchoolYear::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static ?string $slug = 'school-years';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Ano Letivo';
    protected static ?string $pluralModelLabel = 'Ano Letivo';
    protected static ?string $modelLabel = 'Ano Letivo';


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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolYears::route('/'),
            'create' => CreateSchoolYear::route('/create'),
            'edit' => EditSchoolYear::route('/{record}/edit'),
        ];
    }
}
