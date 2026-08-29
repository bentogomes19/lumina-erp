<?php

namespace App\Filament\Pages\Student;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Support\PermissionAccess;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentAttendance extends Page implements HasTable {

    use InteractsWithTable;

    protected static ?string $navigationLabel                = 'Frequência';
    protected static ?string $title                          = 'Frequência';
    protected static ?string $slug                           = 'student-attendance';
    protected static string|\BackedEnum|null $navigationIcon = 'fas-calendar-days';
    protected static ?int $navigationSort                    = 2;

    public string $selectedPeriod = 'all';

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return PermissionAccess::can('student.attendance.view');
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return PermissionAccess::can('student.attendance.view');
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.student.student-attendance';
    }

    /**
     * Define o período usado para filtrar os dados.
     *
     * @param string $period
     *
     * @return void
     */
    public function setPeriod(string $period): void {
        $this->selectedPeriod = $period;
        $this->resetTable();
    }

    /**
     * Retorna os dados completos de frequência para o modelo Blade da página.
     *
     * @return array
     */
    public function getPageData(): array {
        $student = auth()->user()?->student;

        $empty = [
            'student'         => null,
            'currentClass'    => null,
            'stats'           => $this->emptyStats(),
            'subject_stats'   => [],
            'calendar'        => [],
            'selected_period' => $this->selectedPeriod,
            'period_label'    => $this->periodLabel(),
            'min_rate'        => 75.0,
        ];

        if (!$student) {
            return $empty;
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->with(['schoolYear', 'gradeLevel'])
            ->first();

        if (!$currentClass) {
            return array_merge($empty, ['student' => $student]);
        }

        [$start, $end] = $this->periodDateRange();

        $records = Attendance::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->whereBetween('date', [$start, $end])
            ->with(['subject', 'lesson'])
            ->get();

        $stats        = $this->computeStats($records, 75.0);
        $subjectStats = $this->computeSubjectStats($records, 75.0);
        $calendar     = $this->buildCalendar($records);

        return [
            'student'         => $student,
            'currentClass'    => $currentClass,
            'stats'           => $stats,
            'subject_stats'   => $subjectStats,
            'calendar'        => $calendar,
            'selected_period' => $this->selectedPeriod,
            'period_label'    => $this->periodLabel(),
            'min_rate'        => 75.0,
        ];
    }

    /* Compatibilidade retroativa. */
    /**
     * Retorna as estatísticas gerais de frequência carregadas para a página.
     *
     * @return array
     */
    public function getAttendanceStats(): array {
        $data = $this->getPageData();
        return array_merge($data['stats'], ['rate' => $data['stats']['rate']]);
    }

    /* Tabela. */
    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table {
        $student = auth()->user()?->student;

        if (!$student) {
            return $table->query(Attendance::query()->whereRaw('1 = 0'));
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->first();

        if (!$currentClass) {
            return $table->query(Attendance::query()->whereRaw('1 = 0'));
        }

        [$start, $end] = $this->periodDateRange();

        return $table
            ->query(
                Attendance::query()
                    ->where('student_id', $student->id)
                    ->where('class_id', $currentClass->id)
                    ->whereBetween('date', [$start, $end])
                    ->with(['subject', 'lesson'])
                    ->latest('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lesson.start_time')
                    ->label('Horário')
                    ->time('H:i')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($s) => $s?->label() ?? $s)
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Observações')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('lesson.topic')
                    ->label('Tópico da Aula')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AttendanceStatus::options()),

                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->relationship('subject', 'name'),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }

    /* Métodos auxiliares privados. */

    /**
     * Retorna o intervalo de datas correspondente ao período selecionado.
     *
     * @return array
     */
    private function periodDateRange(): array {
        $year = now()->year;

        return match ($this->selectedPeriod) {
            'b1'    => [Carbon::create($year, 1, 1)->startOfDay(),  Carbon::create($year, 3, 31)->endOfDay()],
            'b2'    => [Carbon::create($year, 4, 1)->startOfDay(),  Carbon::create($year, 6, 30)->endOfDay()],
            'b3'    => [Carbon::create($year, 7, 1)->startOfDay(),  Carbon::create($year, 9, 30)->endOfDay()],
            'b4'    => [Carbon::create($year, 10, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()],
            default => [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()],
        };
    }

    /**
     * Retorna o rótulo do período de frequência selecionado.
     *
     * @return string
     */
    private function periodLabel(): string {
        $year = now()->year;

        return match ($this->selectedPeriod) {
            'b1'    => "1º Bimestre $year",
            'b2'    => "2º Bimestre $year",
            'b3'    => "3º Bimestre $year",
            'b4'    => "4º Bimestre $year",
            default => "Ano Letivo $year",
        };
    }

    /**
     * Retorna a estrutura vazia das estatísticas.
     *
     * @return array
     */
    private function emptyStats(): array {
        return [
            'total'              => 0, 'present' => 0, 'absent' => 0, 'late' => 0,
            'excused'            => 0, 'rate' => 0.0, 'alert' => false,
            'remaining_absences' => 0, 'max_allowed_absences' => 0,
        ];
    }

    /**
     * Calcula os totais e percentuais gerais de frequência.
     *
     * @param mixed $records
     * @param float $minRate
     *
     * @return array
     */
    private function computeStats($records, float $minRate): array {
        $total   = $records->count();
        $present = $records->where('status', AttendanceStatus::PRESENT)->count();
        $late    = $records->where('status', AttendanceStatus::LATE)->count();
        $absent  = $records->where('status', AttendanceStatus::ABSENT)->count();
        $excused = $records->where('status', AttendanceStatus::EXCUSED)->count();

        $rate       = $total > 0 ? round(($present + $late + $excused) / $total * 100, 1) : 0.0;
        $maxAllowed = $total > 0 ? (int) floor($total * (1 - $minRate / 100)) : 0;

        return [
            'total'                => $total,
            'present'              => $present,
            'absent'               => $absent,
            'late'                 => $late,
            'excused'              => $excused,
            'rate'                 => $rate,
            'alert'                => $rate < $minRate && $total > 0,
            'max_allowed_absences' => $maxAllowed,
            'remaining_absences'   => $maxAllowed - $absent,
        ];
    }

    /**
     * Calcula os totais e percentuais de frequência agrupados por disciplina.
     *
     * @param mixed $records
     * @param float $minRate
     *
     * @return array
     */
    private function computeSubjectStats($records, float $minRate): array {
        $grouped = $records->groupBy(fn ($r) => $r->subject_id ?? 0);
        $stats   = [];

        foreach ($grouped as $subjectId => $subjectRecords) {
            $subject  = $subjectRecords->first()->subject;
            $sTotal   = $subjectRecords->count();
            $sPresent = $subjectRecords->whereIn('status', [AttendanceStatus::PRESENT, AttendanceStatus::LATE])->count();
            $sAbsent  = $subjectRecords->where('status', AttendanceStatus::ABSENT)->count();
            $sExcused = $subjectRecords->where('status', AttendanceStatus::EXCUSED)->count();
            $sRate    = $sTotal > 0 ? round(($sPresent + $sExcused) / $sTotal * 100, 1) : 0.0;
            $sMax     = $sTotal > 0 ? (int) floor($sTotal * (1 - $minRate / 100)) : 0;

            $stats[] = [
                'subject'            => $subject,
                'total'              => $sTotal,
                'present'            => $sPresent,
                'absent'             => $sAbsent,
                'excused'            => $sExcused,
                'rate'               => $sRate,
                'alert'              => $sRate < $minRate && $sTotal > 0,
                'remaining_absences' => $sMax - $sAbsent,
            ];
        }

        usort($stats, fn ($a, $b) => $a['rate'] <=> $b['rate']);

        return $stats;
    }

    /**
     * Monta os dados mensais do calendário de frequência do estudante.
     *
     * @param mixed $records
     *
     * @return array
     */
    private function buildCalendar($records): array {
        $byDate = $records->groupBy(fn ($r) => $r->date->format('Y-m-d'));
        $months = [];

        for ($offset = 1; $offset >= 0; $offset--) {
            $monthStart = now()->subMonths($offset)->startOfMonth();
            $days       = [];

            for ($d = 1; $d <= $monthStart->daysInMonth; $d++) {
                $dateStr    = $monthStart->copy()->setDay($d)->format('Y-m-d');
                $dayRecords = $byDate[$dateStr] ?? collect();
                $dayStatus  = null;

                if ($dayRecords->isNotEmpty()) {
                    if ($dayRecords->contains('status', AttendanceStatus::ABSENT)) {
                        $dayStatus = 'absent';
                    } elseif ($dayRecords->contains('status', AttendanceStatus::LATE)) {
                        $dayStatus = 'late';
                    } elseif ($dayRecords->contains('status', AttendanceStatus::EXCUSED)) {
                        $dayStatus = 'excused';
                    } else {
                        $dayStatus = 'present';
                    }
                }

                $days[] = [
                    'day'    => $d,
                    'date'   => $dateStr,
                    'dow'    => $monthStart->copy()->setDay($d)->dayOfWeek, /* 0 corresponde a domingo. */
                    'status' => $dayStatus,
                    'count'  => $dayRecords->count(),
                ];
            }

            $months[] = [
                'label'     => ucfirst($monthStart->locale('pt_BR')->translatedFormat('F Y')),
                'first_dow' => $monthStart->dayOfWeek,
                'days'      => $days,
            ];
        }

        return $months;
    }
}
