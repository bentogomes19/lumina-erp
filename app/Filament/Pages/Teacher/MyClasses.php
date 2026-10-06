<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Services\CurrentTeacherService;
use App\Models\Enrollment;
use App\Models\TeacherAssignment;
use App\Models\SchoolYear;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Support\PermissionAccess;
use Illuminate\Support\Collection;

class MyClasses extends Page implements HasTable {

    use HasTeacherPortalAccess;
    use InteractsWithTable;

    protected static ?string $navigationLabel                = 'Minhas Turmas';
    protected static ?string $title                          = 'Minhas Turmas';
    protected static ?string $slug                           = 'teacher-my-classes';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-chalkboard-user';
    protected static ?int $navigationSort                    = 2;
    protected static ?string $teacherPortalPermission        = 'teacher.classes.view';

    public bool $showHistory = false;

    public ?int $selectedClassId = null;

    public function updatedShowHistory(): void {
        $this->selectedClassId = null;
        $this->resetTable();
    }

    public function showStudents(int $classId): void {
        $authorized = app(CurrentTeacherService::class)->assignments()
            ->contains(fn ($assignment) => (int) $assignment->class_id === $classId);

        abort_unless($authorized, 403);
        $this->selectedClassId = $classId;
    }

    public function closeStudents(): void {
        $this->selectedClassId = null;
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.my-classes';
    }

    public function table(Table $table): Table {
        $teacher = app(CurrentTeacherService::class)->current();
        $query = TeacherAssignment::query()
            ->where('teacher_id', $teacher?->id ?? 0)
            ->with(['schoolClass.schoolYear', 'schoolClass.gradeLevel', 'subject']);

        if (!$this->showHistory) {
            $query->whereHas('schoolClass', fn ($class) => $class->where('school_year_id', SchoolYear::current()?->id ?? 0));
        }

        return $table->query($query)
            ->columns([
                TextColumn::make('schoolClass.name')->label('Turma')->searchable(),
                TextColumn::make('subject.name')->label('Disciplina'),
                TextColumn::make('schoolClass.schoolYear.year')->label('Ano letivo')->sortable(),
                TextColumn::make('schoolClass.status')->label('Situação')->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
                TextColumn::make('students_count')->label('Alunos')
                    ->state(fn (TeacherAssignment $record) => Enrollment::query()->where('class_id', $record->class_id)->count()),
            ])
            ->recordActions([
                Action::make('students')->label('Ver alunos')->icon('fas-users')
                    ->action(fn (TeacherAssignment $record) => $this->showStudents($record->class_id)),
                Action::make('grades')->label('Notas')->icon('fas-pen-to-square')
                    ->url(fn (TeacherAssignment $record) => TeacherGrades::getUrl(['class_id' => $record->class_id, 'subject_id' => $record->subject_id], panel: 'professor'))
                    ->visible(fn () => PermissionAccess::can('teacher.grades.view')),
                Action::make('attendance')->label('Frequência')->icon('fas-user-check')
                    ->url(fn (TeacherAssignment $record) => TeacherAttendance::getUrl(['class_id' => $record->class_id, 'subject_id' => $record->subject_id], panel: 'professor'))
                    ->visible(fn () => PermissionAccess::can('teacher.attendance.view')),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
    public function getPageData(): array {
        $service = app(CurrentTeacherService::class);
        $teacher = $service->current();

        if (!$teacher) {
            return [
                'teacher'     => null,
                'assignments' => collect(),
                'historyCount' => 0,
                'currentYear' => null,
                'selectedStudents' => collect(),
            ];
        }

        $allAssignments = $service->assignments($teacher);
        $assignments = $this->showHistory
            ? $allAssignments->filter(fn ($assignment) =>
                (int) $assignment->schoolClass?->school_year_id !== (int) (SchoolYear::current()?->id ?? 0)
            )
            : $service->currentAssignments($teacher);

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
            'teacher'     => $teacher,
            'assignments' => $assignments,
            'historyCount' => $allAssignments->count() - $service->currentAssignments($teacher)->count(),
            'currentYear' => SchoolYear::current()?->year,
            'selectedStudents' => $this->selectedStudents($assignments),
        ];
    }

    private function selectedStudents(Collection $assignments): Collection {
        if (!$this->selectedClassId || !$assignments->contains('class_id', $this->selectedClassId)) {
            return collect();
        }

        return Enrollment::query()
            ->where('class_id', $this->selectedClassId)
            ->with('student')
            ->orderBy('roll_number')
            ->get();
    }
}
