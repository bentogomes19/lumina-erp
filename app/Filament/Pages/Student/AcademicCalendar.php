<?php

namespace App\Filament\Pages\Student;

use App\Enums\HolidayType;
use App\Models\Assessment;
use App\Models\SchoolHoliday;
use App\Models\SchoolYear;
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

    public int $currentMonth;
    public int $currentYear;
    public string $viewMode = 'month'; // month | list
    public array $activeCategories = ['assessment', 'holiday', 'recess', 'school_event', 'period'];
    public ?int $filterSubjectId = null;

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
    }

    // ── Navigation actions ───────────────────────────────────────────────────

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

    public function goToToday(): void
    {
        $this->currentMonth = (int) now()->format('m');
        $this->currentYear  = (int) now()->format('Y');
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
        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfDay();
        $monthEnd   = $monthStart->copy()->endOfMonth()->endOfDay();

        // Collect all events for the month
        $events = $this->collectEvents($currentClass, $schoolYear, $monthStart, $monthEnd);

        // Build calendar grid
        $grid = $this->buildMonthGrid($monthStart, $events);

        // List view: all events for the month sorted by date
        $listEvents = $events->sortBy('date')->values();

        // Upcoming events for sidebar (next 7 days from today)
        $upcoming = $this->getUpcomingEvents($currentClass, $schoolYear);

        // Subjects in the class for filter dropdown
        $subjects = $this->getClassSubjects($currentClass->id);

        return [
            'student'      => $student,
            'currentClass' => $currentClass,
            'schoolYear'   => $schoolYear,
            'monthStart'   => $monthStart,
            'grid'         => $grid,
            'events'       => $events->values(),
            'listEvents'   => $listEvents,
            'upcoming'     => $upcoming,
            'subjects'     => $subjects,
        ];
    }

    // ── Private: event collection ────────────────────────────────────────────

    private function collectEvents($currentClass, $schoolYear, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        $events = collect();

        // 1. Assessments (Avaliações)
        if (in_array('assessment', $this->activeCategories)) {
            $assessments = Assessment::where('class_id', $currentClass->id)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                ->when($this->filterSubjectId, fn ($q) => $q->where('subject_id', $this->filterSubjectId))
                ->with(['subject'])
                ->orderBy('scheduled_at')
                ->get();

            foreach ($assessments as $assessment) {
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
                    'weight'         => $assessment->weight,
                    'icon'           => 'pencil-square',
                    'dot_color'      => '#3b82f6',
                    'bg_color'       => '#eff6ff',
                    'text_color'     => '#1d4ed8',
                ]);
            }
        }

        // 2. Holidays & Recesses (from SchoolHoliday)
        if (! empty(array_intersect(['holiday', 'recess', 'school_event'], $this->activeCategories))) {
            $holidays = SchoolHoliday::active()
                ->when($schoolYear, fn ($q) => $q->forYear($schoolYear->id))
                ->inPeriod($monthStart, $monthEnd)
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
                    'weight'         => null,
                    'icon'           => $icon,
                    'dot_color'      => $dotColor,
                    'bg_color'       => $bgColor,
                    'text_color'     => $textColor,
                ]);
            }
        }

        // 3. Academic period markers (year start/end, bimester boundaries)
        if (in_array('period', $this->activeCategories) && $schoolYear) {
            foreach ($this->getSchoolYearEvents($schoolYear) as $event) {
                $eventDate = Carbon::parse($event['date']);
                if ($eventDate->month === $this->currentMonth && $eventDate->year === $this->currentYear) {
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

        $start      = $schoolYear->starts_at;
        $end        = $schoolYear->ends_at;
        $totalDays  = (int) $start->diffInDays($end);
        $quarter    = (int) ($totalDays / 4);

        return [
            [
                'id'             => 'period_year_start',
                'title'          => 'Início do Ano Letivo ' . $schoolYear->year,
                'description'    => 'Primeiro dia do ano letivo.',
                'date'           => $start->format('Y-m-d'),
                'date_end'       => null,
                'time'           => null,
                'category'       => 'period',
                'category_label' => 'Período Letivo',
                'color'          => 'emerald',
                'subject'        => null,
                'weight'         => null,
                'icon'           => 'academic-cap',
                'dot_color'      => '#10b981',
                'bg_color'       => '#ecfdf5',
                'text_color'     => '#047857',
            ],
            [
                'id'             => 'period_b1_end',
                'title'          => 'Fim do 1º Bimestre',
                'description'    => 'Encerramento do primeiro bimestre.',
                'date'           => $start->copy()->addDays($quarter)->format('Y-m-d'),
                'date_end'       => null,
                'time'           => null,
                'category'       => 'period',
                'category_label' => 'Período Letivo',
                'color'          => 'orange',
                'subject'        => null,
                'weight'         => null,
                'icon'           => 'flag',
                'dot_color'      => '#f97316',
                'bg_color'       => '#fff7ed',
                'text_color'     => '#c2410c',
            ],
            [
                'id'             => 'period_b2_end',
                'title'          => 'Fim do 2º Bimestre',
                'description'    => 'Encerramento do segundo bimestre.',
                'date'           => $start->copy()->addDays($quarter * 2)->format('Y-m-d'),
                'date_end'       => null,
                'time'           => null,
                'category'       => 'period',
                'category_label' => 'Período Letivo',
                'color'          => 'orange',
                'subject'        => null,
                'weight'         => null,
                'icon'           => 'flag',
                'dot_color'      => '#f97316',
                'bg_color'       => '#fff7ed',
                'text_color'     => '#c2410c',
            ],
            [
                'id'             => 'period_b3_end',
                'title'          => 'Fim do 3º Bimestre',
                'description'    => 'Encerramento do terceiro bimestre.',
                'date'           => $start->copy()->addDays($quarter * 3)->format('Y-m-d'),
                'date_end'       => null,
                'time'           => null,
                'category'       => 'period',
                'category_label' => 'Período Letivo',
                'color'          => 'orange',
                'subject'        => null,
                'weight'         => null,
                'icon'           => 'flag',
                'dot_color'      => '#f97316',
                'bg_color'       => '#fff7ed',
                'text_color'     => '#c2410c',
            ],
            [
                'id'             => 'period_year_end',
                'title'          => 'Encerramento do Ano Letivo ' . $schoolYear->year,
                'description'    => 'Último dia do ano letivo.',
                'date'           => $end->format('Y-m-d'),
                'date_end'       => null,
                'time'           => null,
                'category'       => 'period',
                'category_label' => 'Período Letivo',
                'color'          => 'emerald',
                'subject'        => null,
                'weight'         => null,
                'icon'           => 'academic-cap',
                'dot_color'      => '#10b981',
                'bg_color'       => '#ecfdf5',
                'text_color'     => '#047857',
            ],
        ];
    }

    private function getUpcomingEvents($currentClass, $schoolYear): Collection
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
                'days_until'     => (int) now()->diffInDays($assessment->scheduled_at, false),
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
                'days_until'     => (int) now()->diffInDays($holiday->start_date, false),
            ]);
        }

        return $events->sortBy('date')->values();
    }

    // ── Private: calendar grid ───────────────────────────────────────────────

    private function buildMonthGrid(Carbon $monthStart, Collection $events): array
    {
        $daysInMonth    = $monthStart->daysInMonth;
        $firstDayOfWeek = $monthStart->dayOfWeek; // 0 = Sunday

        // Group events by date, expand multi-day events
        $eventsByDate = [];
        foreach ($events as $event) {
            $eventsByDate[$event['date']][] = $event;

            if ($event['date_end'] && $event['date_end'] !== $event['date']) {
                $cursor = Carbon::parse($event['date'])->addDay();
                $rangeEnd = Carbon::parse($event['date_end']);
                while ($cursor->lte($rangeEnd)) {
                    $key = $cursor->format('Y-m-d');
                    $eventsByDate[$key][] = array_merge($event, ['is_continuation' => true]);
                    $cursor->addDay();
                }
            }
        }

        $cells = [];
        $today = now()->format('Y-m-d');

        // Leading empty cells
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $cells[] = ['type' => 'empty', 'day' => null, 'date' => null, 'events' => [], 'is_today' => false, 'is_weekend' => false];
        }

        // Day cells
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

        // Trailing empty cells to complete last week
        $remainder = count($cells) % 7;
        if ($remainder > 0) {
            for ($i = 0; $i < (7 - $remainder); $i++) {
                $cells[] = ['type' => 'empty', 'day' => null, 'date' => null, 'events' => [], 'is_today' => false, 'is_weekend' => false];
            }
        }

        return $cells;
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
        return [
            'student'      => null,
            'currentClass' => null,
            'schoolYear'   => null,
            'monthStart'   => Carbon::create($this->currentYear, $this->currentMonth, 1),
            'grid'         => [],
            'events'       => collect(),
            'listEvents'   => collect(),
            'upcoming'     => collect(),
            'subjects'     => collect(),
        ];
    }
}
