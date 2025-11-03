<?php

namespace App\Filament\Widgets;

use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
class MyClassesTable extends BaseWidget
{
    protected static ?string $heading = 'Minhas Turmas';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('teacher') ?? false;
    }

    public function table(Table $table): Table
    {
        $teacher = auth()->user()?->teacher;
        $classIds = $teacher
            ? TeacherAssignment::where('teacher_id', $teacher->id)->pluck('class_id')->unique()
            : collect([-1]);

        return $table
            ->query(
                SchoolClass::query()
                    ->whereIn('id', $classIds)
                    ->withCount([
                        'students as students_count',
                        'subjects as subjects_count',
                    ])
                    ->with(['schoolYear'])
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')->label('Turma')->searchable()->sortable(),
                TextColumn::make('schoolYear.year')->label('Ano letivo')->sortable(),
                TextColumn::make('students_count')->label('Alunos')->numeric()->alignRight(),
                TextColumn::make('subjects_count')->label('Disciplinas')->numeric()->alignRight(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
