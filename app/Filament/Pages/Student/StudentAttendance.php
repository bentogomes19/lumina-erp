<?php

namespace App\Filament\Pages\Student;

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
        $present = Attendance::where('student_id', $studentId)->where('status', 'present')->count();
        $absent = Attendance::where('student_id', $studentId)->where('status', 'absent')->count();
        $late = Attendance::where('student_id', $studentId)->where('status', 'late')->count();
        
        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'rate' => $attendanceRate,
        ];
    }

    public function table(Table $table): Table
    {
        $studentId = auth()->user()?->student?->id ?? 0;

        return $table
            ->query(
                Attendance::query()
                    ->where('student_id', $studentId)
                    ->with(['subject'])
                    ->latest('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn($state) => $state === 'present',
                        'danger'  => fn($state) => $state === 'absent',
                        'warning' => fn($state) => $state === 'late',
                        'gray'    => fn() => true,
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'present' => 'Presente',
                        'absent'  => 'Falta',
                        'late'    => 'Atraso',
                        default   => $state,
                    })
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
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
