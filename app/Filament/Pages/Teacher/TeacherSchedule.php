<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\LessonStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Lesson;
use App\Services\CurrentTeacherService;
use Filament\Pages\Page;

class TeacherSchedule extends Page
{
    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel = 'Agenda de Aulas';
    protected static ?string $title = 'Agenda de Aulas';
    protected static ?string $slug = 'teacher-schedule';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-calendar-days';
    protected static string|null|\UnitEnum $navigationGroup = 'Portal do Professor';
    protected static ?int $navigationSort = 3;
    protected static ?string $teacherPortalPermission = 'teacher.schedule.view';

    public ?string $filterSchoolYear = '';
    public ?string $filterClass = '';
    public ?string $filterSubject = '';
    public ?string $filterShift = '';

    public function getView(): string
    {
        return 'filament.pages.teacher.teacher-schedule';
    }

    public function getPageData(): array
    {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (! $teacher) {
            return [
                'teacher' => null,
                'lessons' => collect(),
                'filters' => $this->getFilterOptions(collect()),
                'isOnLeave' => false,
            ];
        }

        $isOnLeave = $teacher->status === TeacherStatus::SABBATICAL
            || $teacher->status === TeacherStatus::INACTIVE;

        $assignments = $service->assignments($teacher);
        $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
        $subjectIds = $assignments->pluck('subject_id')->filter()->unique()->values();

        $query = Lesson::query()
            ->where(function ($q) use ($teacher, $classIds, $subjectIds) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($classIds, $subjectIds) {
                        $nested->whereIn('class_id', $classIds)
                            ->whereIn('subject_id', $subjectIds);
                    });
            })
            ->whereIn('status', [LessonStatus::SCHEDULED, LessonStatus::COMPLETED, LessonStatus::RESCHEDULED])
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

        $lessons = $query->orderBy('date')->orderBy('start_time')->get();

        return [
            'teacher' => $teacher,
            'lessons' => $lessons,
            'filters' => $this->getFilterOptions($assignments),
            'isOnLeave' => $isOnLeave,
        ];
    }

    private function getFilterOptions($assignments): array
    {
        $schoolYears = $assignments->pluck('schoolClass.schoolYear')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($y) => [$y->id => $y->name])
            ->all();

        $classes = $assignments->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($c) => [$c->id => $c->name])
            ->all();

        $subjects = $assignments->pluck('subject')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($s) => [$s->id => $s->name])
            ->all();

        $shifts = $assignments->pluck('schoolClass.shift')
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
            ->all();

        return [
            'schoolYears' => $schoolYears,
            'classes' => $classes,
            'subjects' => $subjects,
            'shifts' => $shifts,
        ];
    }

    public function updatedFilterSchoolYear(): void {}
    public function updatedFilterClass(): void {}
    public function updatedFilterSubject(): void {}
    public function updatedFilterShift(): void {}

    public function resetFilters(): void
    {
        $this->filterSchoolYear = '';
        $this->filterClass = '';
        $this->filterSubject = '';
        $this->filterShift = '';
    }
}
