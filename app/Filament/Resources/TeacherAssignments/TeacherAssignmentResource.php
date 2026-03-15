<?php

namespace App\Filament\Resources\TeacherAssignments;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\TeacherAssignments\Pages\CreateTeacherAssignment;
use App\Filament\Resources\TeacherAssignments\Pages\EditTeacherAssignment;
use App\Filament\Resources\TeacherAssignments\Pages\ListTeacherAssignments;
use App\Filament\Resources\TeacherAssignments\Schemas\TeacherAssignmentForm;
use App\Filament\Resources\TeacherAssignments\Tables\TeacherAssignmentsTable;
use App\Models\TeacherAssignment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TeacherAssignmentResource extends BaseAdminResource
{
    protected static ?string $model = TeacherAssignment::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Configurações Acadêmicas';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Alocação de Professores';
    protected static ?string $pluralModelLabel = 'Alocação de Professores';
    protected static ?string $modelLabel = 'Alocação do Professor';

    protected static function viewPermission(): string   { return 'teacher_assignments.view'; }
    protected static function createPermission(): string { return 'teacher_assignments.create'; }
    protected static function editPermission(): string   { return 'teacher_assignments.edit'; }
    protected static function deletePermission(): string { return 'teacher_assignments.delete'; }

    public static function form(Schema $schema): Schema
    {
        return TeacherAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeacherAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTeacherAssignments::route('/'),
            'create' => CreateTeacherAssignment::route('/create'),
            'edit'   => EditTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
