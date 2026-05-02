<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Services\CurrentTeacherService;
use Filament\Pages\Page;

class MyClasses extends Page
{
    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel = 'Minhas Turmas';
    protected static ?string $title = 'Minhas Turmas';
    protected static ?string $slug = 'teacher-my-classes';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-chalkboard-user';
    protected static string|null|\UnitEnum $navigationGroup = 'Portal do Professor';
    protected static ?int $navigationSort = 2;
    protected static ?string $teacherPortalPermission = 'teacher.classes.view';

    public function getView(): string
    {
        return 'filament.pages.teacher.my-classes';
    }

    public function getPageData(): array
    {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (! $teacher) {
            return [
                'teacher' => null,
                'assignments' => collect(),
            ];
        }

        $assignments = $service->assignments($teacher);

        $assignments->each(function ($assignment) {
            $class = $assignment->schoolClass;

            $assignment->students_count = $class?->students()->count() ?? 0;

            $hoursWeekly = null;
            if ($class && $assignment->subject) {
                $pivot = $assignment->subject->gradeLevels()
                    ->where('grade_levels.id', $class->grade_level_id)
                    ->first();
                $hoursWeekly = $pivot?->pivot?->hours_weekly;
            }
            $assignment->hours_weekly = $hoursWeekly;
        });

        return [
            'teacher' => $teacher,
            'assignments' => $assignments,
        ];
    }
}
