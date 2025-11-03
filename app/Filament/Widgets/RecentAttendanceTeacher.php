<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\TeacherAssignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAttendanceTeacher extends BaseWidget
{
    protected static ?string $heading = 'Presenças Recentes (7 dias)';
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
                Attendance::query()
                    ->whereIn('class_id', $classIds)
                    ->where('date', '>=', now()->subDays(7)->toDateString())
                    ->with(['student','subject'])
                    ->latest('date')
            )
            ->columns([
                TextColumn::make('date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('student.name')->label('Aluno')->searchable(),
                TextColumn::make('subject.name')->label('Disciplina')->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($s) => match($s) {
                        'present' => 'Presente',
                        'absent'  => 'Falta',
                        'late'    => 'Atraso',
                        default   => $s,
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
