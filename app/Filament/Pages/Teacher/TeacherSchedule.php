<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\LessonStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Lesson;
use App\Services\CurrentTeacherService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherSchedule extends Page
{
    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel = 'Agenda de Aulas';

    protected static ?string $title = 'Agenda de Aulas';

    protected static ?string $slug = 'teacher-schedule';

    protected static string|null|\BackedEnum $navigationIcon = 'fas-calendar-days';

    protected static ?int $navigationSort = 3;

    protected static ?string $teacherPortalPermission = 'teacher.schedule.view';

    public ?string $filterSchoolYear = '';

    public ?string $filterClass = '';

    public ?string $filterSubject = '';

    public ?string $filterShift = '';

    public int $weekOffset = 0;

    public ?int $selectedLessonId = null;

    public function getView(): string {
        return 'filament.pages.teacher.teacher-schedule-weekly';
    }

    public function getPageData(): array {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (!$teacher) {
            $weekStart = $this->currentWeekStart();

            return [
                'teacher'              => null,
                'lessons'              => collect(),
                'selectedLesson'       => null,
                'pedagogicalFallbacks' => collect(),
                'filters'              => $this->getFilterOptions(collect()),
                'isOnLeave'            => false,
                'weekStart'            => $weekStart,
                'weekEnd'              => $weekStart->copy()->addDays(4),
                'weekDays'             => $this->weekDays($weekStart),
            ];
        }

        $isOnLeave   = $teacher->status === TeacherStatus::SABBATICAL || $teacher->status === TeacherStatus::INACTIVE;
        $assignments = $service->assignments($teacher);
        $classIds    = $assignments->pluck('class_id')->filter()->unique()->values();
        $subjectIds  = $assignments->pluck('subject_id')->filter()->unique()->values();
        $weekStart   = $this->currentWeekStart();
        $weekEnd     = $weekStart->copy()->addDays(4);

        $query = Lesson::query()
            ->where(function ($q) use ($teacher, $classIds, $subjectIds) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($classIds, $subjectIds) {
                        $nested->whereIn('class_id', $classIds)
                            ->whereIn('subject_id', $subjectIds);
                    });
            })->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereIn('status', collect(LessonStatus::cases())->pluck('value')->all())
            ->with(['schoolClass.gradeLevel', 'schoolClass.schoolYear', 'subject', 'schoolYear']);

        if ($this->filterSchoolYear) {
            $query->where('school_year_id', $this->filterSchoolYear);
        }

        if ($this->filterClass) {
            $query->where('class_id', $this->filterClass);
        }

        if ($this->filterSubject) {
            $query->where('subject_id', $this->filterSubject);
        }

        if ($this->filterShift) {
            $query->whereHas('schoolClass', function ($q) {
                $q->where('shift', $this->filterShift);
            });
        }

        $lessons              = $query->orderBy('date')->orderBy('start_time')->get();
        $selectedLesson       = $this->selectedLesson($lessons);
        $pedagogicalFallbacks = $this->pedagogicalFallbacks($lessons);

        return [
            'teacher'              => $teacher,
            'lessons'              => $lessons,
            'selectedLesson'       => $selectedLesson,
            'pedagogicalFallbacks' => $pedagogicalFallbacks,
            'filters'              => $this->getFilterOptions($assignments),
            'isOnLeave'            => $isOnLeave,
            'weekStart'            => $weekStart,
            'weekEnd'              => $weekEnd,
            'weekDays'             => $this->weekDays($weekStart),
        ];
    }

    public function previousWeek(): void {
        $this->weekOffset--;
        $this->selectedLessonId = null;
    }

    public function nextWeek(): void {
        $this->weekOffset++;
        $this->selectedLessonId = null;
    }

    public function currentWeek(): void {
        $this->weekOffset = 0;
        $this->selectedLessonId = null;
    }

    public function selectLesson(int $lessonId): void {
        $this->selectedLessonId = $lessonId;
    }

    public function closeLessonDetails(): void {
        $this->selectedLessonId = null;
    }

    private function currentWeekStart(): Carbon {
        return now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);
    }

    private function weekDays(Carbon $weekStart): Collection {
        return collect(range(0, 4))->map(fn (int $offset) => $weekStart->copy()->addDays($offset));
    }

    private function selectedLesson(Collection $lessons): ?Lesson {
        if ($lessons->isEmpty() || !$this->selectedLessonId) {
            return null;
        }

        return $lessons->firstWhere('id', $this->selectedLessonId);
    }

    private function pedagogicalFallbacks(Collection $lessons): Collection {
        if ($lessons->isEmpty() || ! Schema::hasTable('grade_level_subject')) {
            return collect();
        }

        $availableColumns = collect(['syllabus', 'objectives', 'program_content'])
            ->filter(fn(string $column) => Schema::hasColumn('grade_level_subject', $column))
            ->values();

        if ($availableColumns->isEmpty()) {
            return collect();
        }

        $gradeLevelIds = $lessons
            ->pluck('schoolClass.grade_level_id')
            ->filter()
            ->unique()
            ->values();

        $subjectIds = $lessons
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        if ($gradeLevelIds->isEmpty() || $subjectIds->isEmpty()) {
            return collect();
        }

        return DB::table('grade_level_subject')
            ->select(array_merge(['grade_level_id', 'subject_id'], $availableColumns->all()))
            ->whereIn('grade_level_id', $gradeLevelIds)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->keyBy(fn ($row) => $this->gradeLevelSubjectKey($row->grade_level_id, $row->subject_id));
    }

    public function gradeLevelSubjectKey(?int $gradeLevelId, ?int $subjectId): string {
        return "{$gradeLevelId}:{$subjectId}";
    }

    private function getFilterOptions($assignments): array {
        $schoolYears = $assignments->pluck('schoolClass.schoolYear')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn($y) => [$y->id => $y->name])
            ->all();

        $classes = $assignments->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn($c) => [$c->id => $c->name])
            ->all();

        $subjects = $assignments->pluck('subject')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn($s) => [$s->id => $s->name])
            ->all();

        $shifts = $assignments->pluck('schoolClass.shift')
            ->filter()
            ->unique()
            ->mapWithKeys(fn($s) => [$s->value => $s->label()])
            ->all();

        return [
            'schoolYears' => $schoolYears,
            'classes'     => $classes,
            'subjects'    => $subjects,
            'shifts'      => $shifts,
        ];
    }

    public function updatedFilterSchoolYear(): void {
        $this->selectedLessonId = null;
    }

    public function updatedFilterClass(): void {
        $this->selectedLessonId = null;
    }

    public function updatedFilterSubject(): void {
        $this->selectedLessonId = null;
    }

    public function updatedFilterShift(): void {
        $this->selectedLessonId = null;
    }

    public function resetFilters(): void {
        $this->filterSchoolYear = '';
        $this->filterClass      = '';
        $this->filterSubject    = '';
        $this->filterShift      = '';
        $this->selectedLessonId = null;
    }
}
