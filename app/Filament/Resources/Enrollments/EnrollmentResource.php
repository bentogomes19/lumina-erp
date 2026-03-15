<?php

namespace App\Filament\Resources\Enrollments;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentDocumentsRelationManager;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentLogsRelationManager;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Resources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EnrollmentResource extends BaseAdminResource
{
    protected static ?string $model = Enrollment::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Matrículas';
    protected static ?string $pluralModelLabel = 'Matrículas';
    protected static ?string $modelLabel = 'Matrícula';

    protected static function viewPermission(): string   { return 'enrollments.view'; }
    protected static function createPermission(): string { return 'enrollments.create'; }
    protected static function editPermission(): string   { return 'enrollments.edit'; }
    protected static function deletePermission(): string { return 'enrollments.delete'; }

    public static function form(Schema $schema): Schema
    {
        return EnrollmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EnrollmentLogsRelationManager::class,
            EnrollmentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEnrollments::route('/'),
            'create' => CreateEnrollment::route('/create'),
            'edit'   => EditEnrollment::route('/{record}/edit'),
        ];
    }
}
