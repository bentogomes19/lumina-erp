<?php

namespace App\Filament\Resources\Grades;

use App\Filament\Resources\Grades\Pages\CreateGrade;
use App\Filament\Resources\Grades\Pages\EditGrade;
use App\Filament\Resources\Grades\Pages\ListGrades;
use App\Filament\Resources\Grades\Schemas\GradeForm;
use App\Filament\Resources\Grades\Tables\GradesTable;
use App\Models\Enrollment;
use App\Models\Grade;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Acadêmico';
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Notas';
    protected static ?string $recordTitleAttribute = 'Notas';
    protected static ?string $pluralModelLabel = 'Notas';

    protected static ?string $modelLabel = 'Nota';

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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrades::route('/'),
            'create' => CreateGrade::route('/create'),
            'edit' => EditGrade::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('teacher') || auth()->user()->hasRole('admin');
    }

    /**
     * Antes de criar o registro, completar student_id (e garantir class_id) a partir da matrícula.
     */
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['enrollment_id'])) {
            $enrollment = Enrollment::find($data['enrollment_id']);

            if ($enrollment) {
                // garante que o student_id vai para o banco
                $data['student_id'] = $enrollment->student_id;

                // opcional: garantir que class_id bate com a matrícula
                if (! empty($enrollment->class_id)) {
                    $data['class_id'] = $enrollment->class_id;
                }
            }
        }

        return $data;
    }

    /**
     * Opcional: na edição, se alterarem a matrícula, sincroniza student_id de novo.
     */
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
