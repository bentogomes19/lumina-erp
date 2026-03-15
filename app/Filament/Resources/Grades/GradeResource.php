<?php

namespace App\Filament\Resources\Grades;

use App\Filament\Resources\BaseAdminResource;
use App\Filament\Resources\Grades\Pages\CreateGrade;
use App\Filament\Resources\Grades\Pages\EditGrade;
use App\Filament\Resources\Grades\Pages\ListGrades;
use App\Filament\Resources\Grades\Schemas\GradeForm;
use App\Filament\Resources\Grades\Tables\GradesTable;
use App\Models\Enrollment;
use App\Models\Grade;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GradeResource extends BaseAdminResource
{
    protected static ?string $model = Grade::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Notas';
    protected static ?string $pluralModelLabel = 'Notas';
    protected static ?string $modelLabel = 'Nota';

    protected static function viewPermission(): string   { return 'grades.view'; }
    protected static function createPermission(): string { return 'grades.create'; }
    protected static function editPermission(): string   { return 'grades.edit'; }
    protected static function deletePermission(): string { return 'grades.delete'; }

    /** Professores também acessam o módulo de notas */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole(['admin', 'ti', 'secretaria'])) {
            return true;
        }
        return $user->hasRole('teacher') && $user->can('grades.view.own');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return GradeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GradesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGrades::route('/'),
            'create' => CreateGrade::route('/create'),
            'edit'   => EditGrade::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['enrollment_id'])) {
            $enrollment = Enrollment::find($data['enrollment_id']);
            if ($enrollment) {
                $data['student_id'] = $enrollment->student_id;
                if (! empty($enrollment->class_id)) {
                    $data['class_id'] = $enrollment->class_id;
                }
            }
        }
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['enrollment_id'])) {
            $enrollment = Enrollment::find($data['enrollment_id']);
            if ($enrollment) {
                $data['student_id'] = $enrollment->student_id;
                if (! empty($enrollment->class_id)) {
                    $data['class_id'] = $enrollment->class_id;
                }
            }
        }
        return $data;
    }
}
