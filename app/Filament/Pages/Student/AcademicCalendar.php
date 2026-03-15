<?php

namespace App\Filament\Pages\Student;

use App\Enums\HolidayType;
use App\Models\Assessment;
use App\Models\SchoolHoliday;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AcademicCalendar extends Page
{
    protected static ?string $navigationLabel = 'Calendário';
    protected static ?string $title = 'Calendário Acadêmico';
    protected static ?string $slug = 'academic-calendar';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static ?int $navigationSort = 4;

    // ── State ────────────────────────────────────────────────────────────────

    public int    $currentMonth;
    public int    $currentYear;
    public string $viewMode       = 'month'; // month | week | list
    public string $weekStart      = '';      // ISO date of the Sunday starting the displayed week
    public array  $activeCategories = ['assessment', 'holiday', 'recess', 'school_event', 'period'];
    public ?int   $filterSubjectId  = null;

    // ── Access ───────────────────────────────────────────────────────────────

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
        return 'filament.pages.student.academic-calendar';
    }

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->currentMonth = (int) now()->format('m');
        $this->currentYear  = (int) now()->format('Y');
        $this->weekStart    = now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
    }

    // ── Month navigation ─────────────────────────────────────────────────────

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear  = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear  = $date->year;
    }

    // ── Week navigation ──────────────────────────────────────────────────────

    public function previousWeek(): void
    {
        $date            = Carbon::parse($this->weekStart)->subWeek();
        $this->weekStart = $date->format('Y-m-d');
        // keep month/year in sync so the month grid is consistent when switching back
        $this->currentMonth = $date->month;
        $this->currentYear  = $date->year;
    }

    public function nextWeek(): void
    {
        $date            = Carbon::parse($this->weekStart)->addWeek();
        $this->weekStart = $date->format('Y-m-d');
        $this->currentMonth = $date->month;
        $this->currentYear  = $date->year;
    }

    // ── Go to today ───────────────────────────────────────────────────────────

    public function goToToday(): void
    {
        $this->currentMonth = (int) now()->format('m');
        $this->currentYear  = (int) now()->format('Y');
        $this->weekStart    = now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
    }

    // ── View mode ────────────────────────────────────────────────────────────

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;

        if ($mode === 'week') {
            // Sync weekStart to the week that contains the 1st of the displayed month
            $this->weekStart = Carbon::create($this->currentYear, $this->currentMonth, 1)
                ->startOfWeek(Carbon::SUNDAY)
                ->format('Y-m-d');
        }

        if ($mode === 'month' || $mode === 'list') {
            // Snap displayed month to the week's midpoint (Thursday) so the month
            // always reflects the week the user was looking at in week view.
            $midWeek = Carbon::parse($this->weekStart)->addDays(3);
            $this->currentMonth = $midWeek->month;
            $this->currentYear  = $midWeek->year;
        }
    }

    // ── Filter actions ───────────────────────────────────────────────────────

    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->activeCategories)) {
            $this->activeCategories = array_values(
                array_filter($this->activeCategories, fn ($c) => $c !== $category)
            );
        } else {
            $this->activeCategories[] = $category;
        }
    }

    public function setSubjectFilter(?int $subjectId): void
    {
        $this->filterSubjectId = $subjectId;
    }

    // ── Export stubs ─────────────────────────────────────────────────────────

    public function exportPdf(): void
    {
        // TODO: generate and stream PDF for the current month
        $this->dispatch('notify', ['message' => 'Exportação em PDF em breve.', 'type' => 'info']);
    }

    public function exportIcal(): void
    {
        // TODO: generate and stream .ics for the current period
        $this->dispatch('notify', ['message' => 'Exportação iCal em breve.', 'type' => 'info']);
    }

    // ── Data ─────────────────────────────────────────────────────────────────

    public function getPageData(): array
    {
        $student = auth()->user()?->student;

        if (! $student) {
            return $this->emptyData();
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->with(['schoolYear', 'gradeLevel'])
            ->first();

        if (! $currentClass) {
            return array_merge($this->emptyData(), ['student' => $student]);
        }

        $schoolYear = $currentClass->schoolYear;

        // Determine the date window to load events for
        if ($this->viewMode === 'week') {
            $periodStart = Carbon::parse($this->weekStart)->startOfDay();
            $periodEnd   = $periodStart->copy()->addDays(6)->endOfDay();
        } else {
            $periodStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfDay();
            $periodEnd   = $periodStart->copy()->endOfMonth()->endOfDay();
        }

        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfDay();

        // Preload teacher assignments for subject→teacher lookup
        $teacherMap = $this->loadTeacherMap($currentClass->id);

        // Collect all events for the period
        $events = $this->collectEvents($currentClass, $schoolYear, $periodStart, $periodEnd, $teacherMap);

        // Build calendar grid
        $grid     = $this->viewMode !== 'week' ? $this->buildMonthGrid($monthStart, $events) : [];
        $weekGrid = $this->viewMode === 'week'
            ? $this->buildWeekGrid(Carbon::parse($this->weekStart), $events)
            : [];

        // List view: all events sorted by date
        $listEvents = $events->sortBy('date')->values();

        // Upcoming events for sidebar (next 14 days)
        $upcoming = $this->getUpcomingEvents($currentClass, $schoolYear, $teacherMap);

        // Subjects for filter dropdown
        $subjects = $this->getClassSubjects($currentClass->id);

        return [
            'student'      => $student,
            'currentClass' => $currentClass,
            'schoolYear'   => $schoolYear,
            'monthStart'   => $monthStart,
            'weekStart'    => Carbon::parse($this->weekStart),
            'grid'         => $grid,
            'weekGrid'     => $weekGrid,
            'events'       => $events->values(),
            'listEvents'   => $listEvents,
            'upcoming'     => $upcoming,
            'subjects'     => $subjects,
        ];
    }

    // ── Private: teacher map ─────────────────────────────────────────────────

    private function loadTeacherMap(int $classId): array
    {
        try {
            return TeacherAssignment::where('class_id', $classId)
                ->with('teacher.user')
                ->get()
                ->mapWithKeys(fn ($a) => [
                    $a->subject_id => $a->teacher?->user?->name,
                ])
                ->filter()
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Private: event collection ────────────────────────────────────────────

    private function collectEvents($currentClass, $schoolYear, Carbon $start, Carbon $end, array $teacherMap = []): Collection
    {
        $events = collect();

        // 1. Assessments (Avaliações)
        if (in_array('assessment', $this->activeCategories)) {
            $assessments = Assessment::where('class_id', $currentClass->id)
                ->whereBetween('scheduled_at', [$start, $end])
                ->when($this->filterSubjectId, fn ($q) => $q->where('subject_id', $this->filterSubjectId))
                ->with(['subject'])
                ->orderBy('scheduled_at')
                ->get();

            foreach ($assessments as $assessment) {
                $teacherName = $teacherMap[$assessment->subject_id] ?? null;
                $events->push([
                    'id'             => 'assessment_' . $assessment->id,
                    'title'          => $assessment->title,
                    'description'    => 'Avaliação de ' . ($assessment->subject?->name ?? '—'),
                    'date'           => $assessment->scheduled_at->format('Y-m-d'),
                    'date_end'       => null,
                    'time'           => $assessment->scheduled_at->format('H:i') !== '00:00' ? $assessment->scheduled_at->format('H:i') : null,
                    'category'       => 'assessment',
                    'category_label' => 'Avaliação',
                    'color'          => 'blue',
                    'subject'        => $assessment->subject?->name,
                    'teacher'        => $teacherName,
                    'weight'         => $assessment->weight,
                    'location'       => null,
                    'icon'           => 'pencil-square',
                    'dot_color'      => '#3b82f6',
                    'bg_color'       => '#eff6ff',
                    'text_color'     => '#1d4ed8',
                    'impacts_grade'  => true,
                    'impacts_freq'   => false,
                ]);
            }
        }

        // 2. Holidays & Recesses (from SchoolHoliday)
        if (! empty(array_intersect(['holiday', 'recess', 'school_event'], $this->activeCategories))) {
            $holidays = SchoolHoliday::active()
                ->when($schoolYear, fn ($q) => $q->forYear($schoolYear->id))
                ->inPeriod($start, $end)
                ->orderBy('start_date')
                ->get();

            foreach ($holidays as $holiday) {
                $category = match ($holiday->type) {
                    HolidayType::SCHOOL_RECESS  => 'recess',
                    HolidayType::SCHOOL_EVENT   => 'school_event',
                    HolidayType::EXAM_PERIOD    => 'assessment',
                    default                     => 'holiday',
                };

                if (! in_array($category, $this->activeCategories)) {
                    continue;
                }

                [$dotColor, $bgColor, $textColor] = match ($holiday->type) {
                    HolidayType::NATIONAL_HOLIDAY  => ['#ef4444', '#fef2f2', '#b91c1c'],
                    HolidayType::STATE_HOLIDAY     => ['#f97316', '#fff7ed', '#c2410c'],
                    HolidayType::MUNICIPAL_HOLIDAY => ['#f59e0b', '#fffbeb', '#b45309'],
                    HolidayType::SCHOOL_RECESS     => ['#8b5cf6', '#f5f3ff', '#6d28d9'],
                    HolidayType::SCHOOL_EVENT      => ['#10b981', '#ecfdf5', '#047857'],
                    HolidayType::EXAM_PERIOD       => ['#6366f1', '#eef2ff', '#4338ca'],
                    default                        => ['#6b7280', '#f9fafb', '#374151'],
                };

                $icon = match ($holiday->type) {
                    HolidayType::SCHOOL_RECESS  => 'sun',
                    HolidayType::SCHOOL_EVENT   => 'star',
                    HolidayType::EXAM_PERIOD    => 'pencil-square',
                    default                     => 'flag',
                };

                $events->push([
                    'id'             => 'holiday_' . $holiday->id,
                    'title'          => $holiday->name,
                    'description'    => $holiday->description ?? $holiday->type->label(),
                    'date'           => $holiday->start_date->format('Y-m-d'),
                    'date_end'       => $holiday->end_date->format('Y-m-d'),
                    'time'           => null,
                    'category'       => $category,
                    'category_label' => $holiday->type->label(),
                    'color'          => 'red',
                    'subject'        => null,
                    'teacher'        => null,
                    'weight'         => null,
                    'location'       => null,
                    'icon'           => $icon,
                    'dot_color'      => $dotColor,
                    'bg_color'       => $bgColor,
                    'text_color'     => $textColor,
                    'impacts_grade'  => false,
                    'impacts_freq'   => in_array($holiday->type, [HolidayType::SCHOOL_EVENT]),
                ]);
            }
        }

        // 3. Academic period markers (year start/end, bimester boundaries)
        if (in_array('period', $this->activeCategories) && $schoolYear) {
            foreach ($this->getSchoolYearEvents($schoolYear) as $event) {
                $eventDate = Carbon::parse($event['date']);
                if ($eventDate->between($start, $end)) {
                    $events->push($event);
                }
            }
        }

        return $events;
    }

    private function getSchoolYearEvents($schoolYear): array
    {
        if (! $schoolYear->starts_at || ! $schoolYear->ends_at) {
            return [];
        }

        $start     = $schoolYear->starts_at;
        $end       = $schoolYear->ends_at;
        $totalDays = (int) $start->diffInDays($end);
        $quarter   = (int) ($totalDays / 4);

        $base = [
            'time' => null, 'category' => 'period', 'category_label' => 'Período Letivo',
            'subject' => null, 'teacher' => null, 'weight' => null, 'location' => null,
            'date_end' => null, 'impacts_grade' => false, 'impacts_freq' => false,
        ];

        return [
            array_merge($base, [
                'id'          => 'period_year_start',
                'title'       => 'Início do Ano Letivo ' . $schoolYear->year,
                'description' => 'Primeiro dia do ano letivo.',
                'date'        => $start->format('Y-m-d'),
                'icon'        => 'academic-cap',
                'dot_color'   => '#10b981', 'bg_color' => '#ecfdf5', 'text_color' => '#047857',
                'color'       => 'emerald',
            ]),
            array_merge($base, [
                'id'          => 'period_b1_end',
                'title'       => 'Fim do 1º Bimestre',
                'description' => 'Encerramento do primeiro bimestre.',
                'date'        => $start->copy()->addDays($quarter)->format('Y-m-d'),
                'icon'        => 'flag',
                'dot_color'   => '#f97316', 'bg_color' => '#fff7ed', 'text_color' => '#c2410c',
                'color'       => 'orange',
            ]),
            array_merge($base, [
                'id'          => 'period_b2_end',
                'title'       => 'Fim do 2º Bimestre',
                'description' => 'Encerramento do segundo bimestre.',
                'date'        => $start->copy()->addDays($quarter * 2)->format('Y-m-d'),
                'icon'        => 'flag',
                'dot_color'   => '#f97316', 'bg_color' => '#fff7ed', 'text_color' => '#c2410c',
                'color'       => 'orange',
            ]),
            array_merge($base, [
                'id'          => 'period_b3_end',
                'title'       => 'Fim do 3º Bimestre',
                'description' => 'Encerramento do terceiro bimestre.',
                'date'        => $start->copy()->addDays($quarter * 3)->format('Y-m-d'),
                'icon'        => 'flag',
                'dot_color'   => '#f97316', 'bg_color' => '#fff7ed', 'text_color' => '#c2410c',
                'color'       => 'orange',
            ]),
            array_merge($base, [
                'id'          => 'period_year_end',
                'title'       => 'Encerramento do Ano Letivo ' . $schoolYear->year,
                'description' => 'Último dia do ano letivo.',
                'date'        => $end->format('Y-m-d'),
                'icon'        => 'academic-cap',
                'dot_color'   => '#10b981', 'bg_color' => '#ecfdf5', 'text_color' => '#047857',
                'color'       => 'emerald',
            ]),
        ];
    }

    private function getUpcomingEvents($currentClass, $schoolYear, array $teacherMap = []): Collection
    {
        $start = now()->startOfDay();
        $end   = now()->addDays(14)->endOfDay();

        $events = collect();

        $assessments = Assessment::where('class_id', $currentClass->id)
            ->whereBetween('scheduled_at', [$start, $end])
            ->with(['subject'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        foreach ($assessments as $assessment) {
            $events->push([
                'title'          => $assessment->title,
                'date'           => $assessment->scheduled_at->format('Y-m-d'),
                'category_label' => 'Avaliação',
                'subject'        => $assessment->subject?->name,
                'dot_color'      => '#3b82f6',
                'days_until'     => max(0, (int) now()->startOfDay()->diffInDays($assessment->scheduled_at->startOfDay(), false)),
            ]);
        }

        $holidays = SchoolHoliday::active()
            ->when($schoolYear, fn ($q) => $q->forYear($schoolYear->id))
            ->where('start_date', '>=', $start)
            ->where('start_date', '<=', $end)
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        foreach ($holidays as $holiday) {
            $events->push([
                'title'          => $holiday->name,
                'date'           => $holiday->start_date->format('Y-m-d'),
                'category_label' => $holiday->type->label(),
                'subject'        => null,
                'dot_color'      => '#8b5cf6',
                'days_until'     => max(0, (int) now()->startOfDay()->diffInDays($holiday->start_date->startOfDay(), false)),
            ]);
        }

        return $events->sortBy('date')->values();
    }

    // ── Private: calendar grids ───────────────────────────────────────────────

    private function buildMonthGrid(Carbon $monthStart, Collection $events): array
    {
        $daysInMonth    = $monthStart->daysInMonth;
        $firstDayOfWeek = $monthStart->dayOfWeek; // 0 = Sunday

        $eventsByDate = $this->groupEventsByDate($events);
        $cells        = [];
        $today        = now()->format('Y-m-d');

        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $cells[] = ['type' => 'empty', 'day' => null, 'date' => null, 'events' => [], 'is_today' => false, 'is_weekend' => false];
        }

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = $monthStart->copy()->setDay($d);
            $dateStr = $date->format('Y-m-d');
            $cells[] = [
                'type'       => 'day',
                'day'        => $d,
                'date'       => $dateStr,
                'events'     => $eventsByDate[$dateStr] ?? [],
                'is_today'   => $dateStr === $today,
                'is_weekend' => $date->isWeekend(),
            ];
        }

        $remainder = count($cells) % 7;
        if ($remainder > 0) {
            for ($i = 0; $i < (7 - $remainder); $i++) {
                $cells[] = ['type' => 'empty', 'day' => null, 'date' => null, 'events' => [], 'is_today' => false, 'is_weekend' => false];
            }
        }

        return $cells;
    }

    private function buildWeekGrid(Carbon $weekStartDate, Collection $events): array
    {
        $eventsByDate = $this->groupEventsByDate($events);
        $today        = now()->format('Y-m-d');
        $cells        = [];

        for ($i = 0; $i < 7; $i++) {
            $date    = $weekStartDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $cells[] = [
                'type'       => 'day',
                'day'        => $date->day,
                'date'       => $dateStr,
                'month_name' => ucfirst($date->locale('pt_BR')->translatedFormat('M')),
                'events'     => $eventsByDate[$dateStr] ?? [],
                'is_today'   => $dateStr === $today,
                'is_weekend' => $date->isWeekend(),
                'dow'        => $date->dayOfWeek,
            ];
        }

        return $cells;
    }

    private function groupEventsByDate(Collection $events): array
    {
        $byDate = [];
        foreach ($events as $event) {
            $byDate[$event['date']][] = $event;

            if ($event['date_end'] && $event['date_end'] !== $event['date']) {
                $cursor   = Carbon::parse($event['date'])->addDay();
                $rangeEnd = Carbon::parse($event['date_end']);
                while ($cursor->lte($rangeEnd)) {
                    $key = $cursor->format('Y-m-d');
                    $byDate[$key][] = array_merge($event, ['is_continuation' => true]);
                    $cursor->addDay();
                }
            }
        }

        return $byDate;
    }

    // ── Private: subjects in class ───────────────────────────────────────────

    private function getClassSubjects(int $classId): Collection
    {
        $subjectIds = TeacherAssignment::where('class_id', $classId)
            ->pluck('subject_id')
            ->unique();

        return Subject::whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function emptyData(): array
    {
        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1);

        return [
            'student'      => null,
            'currentClass' => null,
            'schoolYear'   => null,
            'monthStart'   => $monthStart,
            'weekStart'    => Carbon::parse($this->weekStart ?: now()->startOfWeek(Carbon::SUNDAY)),
            'grid'         => [],
            'weekGrid'     => [],
            'events'       => collect(),
            'listEvents'   => collect(),
            'upcoming'     => collect(),
            'subjects'     => collect(),
        ];
    }
}
