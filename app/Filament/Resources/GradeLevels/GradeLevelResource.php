<?php

namespace App\Filament\Resources\GradeLevels;

use App\Filament\Resources\GradeLevels\Pages\CreateGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\EditGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\ListGradeLevels;
use App\Filament\Resources\GradeLevels\RelationManagers\SubjectsRelationManager;
use App\Filament\Resources\GradeLevels\Schemas\GradeLevelForm;
use App\Filament\Resources\GradeLevels\Tables\GradeLevelsTable;
use App\Models\GradeLevel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GradeLevelResource extends Resource
{
    protected static ?string $model = GradeLevel::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $slug = 'grade-levels';

    protected static ?string $navigationLabel = 'Séries / Etapas';

    protected static ?string $pluralModelLabel = 'Séries / Etapas';
    protected static ?string $modelLabel = 'Séries / Etapas';

    public static function form(Schema $schema): Schema
    {
        return GradeLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GradeLevelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SubjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradeLevels::route('/'),
            'create' => CreateGradeLevel::route('/create'),
            'edit' => EditGradeLevel::route('/{record}/edit'),
        ];
    }
}
