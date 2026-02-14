<?php

namespace App\Filament\Pages\Student;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAttendance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Frequência';
    protected static ?string $title = 'Frequência';
    protected static ?string $slug = 'student-attendance';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.student-attendance';
    }

    // Estatísticas de frequência
    public function getAttendanceStats(): array
    {
        $studentId = auth()->user()?->student?->id ?? 0;
        
        $total = Attendance::where('student_id', $studentId)->count();
        $present = Attendance::where('student_id', $studentId)
            ->where('status', AttendanceStatus::PRESENT)
            ->count();
        $absent = Attendance::where('student_id', $studentId)
            ->where('status', AttendanceStatus::ABSENT)
            ->count();
        $late = Attendance::where('student_id', $studentId)
            ->where('status', AttendanceStatus::LATE)
            ->count();
        $excused = Attendance::where('student_id', $studentId)
            ->where('status', AttendanceStatus::EXCUSED)
            ->count();
        
        // Taxa de frequência (presenças + atrasos) / total
        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'rate' => $attendanceRate,
            'alert' => $attendanceRate < 75.0,
        ];
    }

    public function table(Table $table): Table
    {
        $studentId = auth()->user()?->student?->id ?? 0;

        return $table
            ->query(
                Attendance::query()
                    ->where('student_id', $studentId)
                    ->with(['subject', 'lesson'])
                    ->latest('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lesson.start_time')
                    ->label('Horário')
                    ->time('H:i')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn($state) => $state === AttendanceStatus::PRESENT,
                        'danger'  => fn($state) => $state === AttendanceStatus::ABSENT,
                        'warning' => fn($state) => $state === AttendanceStatus::LATE,
                        'info'    => fn($state) => $state === AttendanceStatus::EXCUSED,
                    ])
                    ->formatStateUsing(fn($state) => $state?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lesson.topic')
                    ->label('Tópico da Aula')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('notes')
                    ->label('Observações')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AttendanceStatus::options()),

                SelectFilter::make('month')
                    ->label('Mês')
                    ->options([
                        1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',
                        7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez',
                    ])
                    ->query(function ($query, $data) {
                        if (! empty($data['value'])) {
                            $query->whereMonth('date', $data['value']);
                        }
                    }),

                SelectFilter::make('year')
                    ->label('Ano')
                    ->options(function () {
                        $y = now()->year;
                        return [$y-1 => (string)($y-1), $y => (string)$y, $y+1 => (string)($y+1)];
                    })
                    ->query(function ($q, $data) {
                        if (! empty($data['value'])) {
                            $q->whereYear('date', $data['value']);
                        }
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
