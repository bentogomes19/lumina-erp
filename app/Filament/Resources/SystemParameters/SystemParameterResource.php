<?php

namespace App\Filament\Resources\SystemParameters;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\SystemParameters\Pages\EditSystemParameter;
use App\Filament\Resources\SystemParameters\Pages\ListSystemParameters;
use App\Filament\Resources\SystemParameters\Schemas\SystemParameterForm;
use App\Filament\Resources\SystemParameters\Tables\SystemParametersTable;
use App\Models\SystemParameter;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SystemParameterResource extends BaseAdminResource
{
    protected static ?string $model = SystemParameter::class;
    protected static string|null|BackedEnum $navigationIcon = 'fas-sliders';
    protected static string|null|\UnitEnum $navigationGroup = 'Administração';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Parametrizações do Sistema';
    protected static ?string $pluralModelLabel = 'Parametrizações do Sistema';
    protected static ?string $modelLabel = 'Parametrização';
    protected static ?string $recordTitleAttribute = 'name';

    protected static function viewPermission(): string { return 'system.settings.manage'; }
    protected static function createPermission(): string { return 'system.settings.manage'; }
    protected static function editPermission(): string { return 'system.settings.manage'; }
    protected static function deletePermission(): string { return 'system.settings.manage'; }

    public static function canCreate(): bool { return false; }

    public static function form(Schema $schema): Schema { return SystemParameterForm::configure($schema); }
    public static function table(Table $table): Table { return SystemParametersTable::configure($table); }
    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => ListSystemParameters::route('/'),
            'edit' => EditSystemParameter::route('/{record}/edit'),
        ];
    }
}
