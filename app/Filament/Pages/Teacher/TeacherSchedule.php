<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\LessonStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Lesson;
use App\Models\SchoolYear;
use App\Services\CurrentTeacherService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherSchedule extends Page implements HasTable {

    use HasTeacherPortalAccess;
    use InteractsWithTable;

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

    public function mount(): void {
        $this->filterSchoolYear = (string) (SchoolYear::current()?->id ?? '');
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.teacher-schedule';
    }

    public function table(Table $table): Table {
        $lessonIds = $this->getPageData()['lessons']->pluck('id')->all();

        return $table->query(Lesson::query()->whereIn('id', $lessonIds)->with(['schoolClass.schoolYear', 'subject']))
            ->columns([
                TextColumn::make('date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('start_time')->label('Início')->dateTime('H:i')->sortable(),
                TextColumn::make('end_time')->label('Fim')->dateTime('H:i'),
                TextColumn::make('schoolClass.name')->label('Turma'),
                TextColumn::make('subject.name')->label('Disciplina'),
                TextColumn::make('topic')->label('Tema')->limit(50),
                TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
            ])
            ->recordActions([
                Action::make('details')->label('Detalhes')->icon('fas-eye')
                    ->action(fn (Lesson $record) => $this->selectLesson($record->id)),
            ])
            ->defaultSort('date');
    }

    private function lessonDetailComponents(): array {
        $lesson = $this->getPageData()['selectedLesson'];
        if (!$lesson) {
            return [];
        }

        return [
            Text::make('Turma: '.($lesson->schoolClass?->name ?? '—')),
            Text::make('Disciplina: '.($lesson->subject?->name ?? '—')),
            Text::make('Tema: '.($lesson->topic ?: 'Não informado')),
            Text::make('Conteúdo: '.($lesson->content ?: 'Não informado')),
            Actions::make([
                Action::make('closeLessonDetails')->label('Fechar detalhes')->action(fn () => $this->closeLessonDetails()),
            ]),
        ];
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
        $weekStart   = $this->currentWeekStart();
        $weekEnd     = $weekStart->copy()->addDays(4);

        $query = $service->scopeAssignedPairs(Lesson::query(), $assignments)
            ->where('teacher_id', $teacher->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
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

    /**
     * Retorna a exibição para a semana anterior.
     *
     * @return void
     */
    public function previousWeek(): void {
        $this->weekOffset--;
        $this->selectedLessonId = null;
    }

    /**
     * Avança a exibição para a próxima semana.
     *
     * @return void
     */
    public function nextWeek(): void {
        $this->weekOffset++;
        $this->selectedLessonId = null;
    }

    /**
     * Retorna os dados da semana atual.
     *
     * @return void
     */
    public function currentWeek(): void {
        $this->weekOffset       = 0;
        $this->selectedLessonId = null;
    }

    /**
     * Seleciona uma aula para exibir seus detalhes.
     *
     * @param int $lessonId
     *
     * @return void
     */
    public function selectLesson(int $lessonId): void {
        $this->selectedLessonId = $lessonId;
    }

    /**
     * Fecha os detalhes da aula selecionada.
     *
     * @return void
     */
    public function closeLessonDetails(): void {
        $this->selectedLessonId = null;
    }

    /**
     * Retorna a data inicial da semana atual.
     *
     * @return Carbon
     */
    private function currentWeekStart(): Carbon {
        return now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);
    }

    /**
     * Retorna os dias que compõem a semana exibida.
     *
     * @param Carbon $weekStart
     *
     * @return Collection
     */
    private function weekDays(Carbon $weekStart): Collection {
        return collect(range(0, 4))->map(fn (int $offset) => $weekStart->copy()->addDays($offset));
    }

    /**
     * Retorna a aula atualmente selecionada.
     *
     * @param Collection $lessons
     *
     * @return Lesson|null
     */
    private function selectedLesson(Collection $lessons): ?Lesson {
        if ($lessons->isEmpty() || !$this->selectedLessonId) {
            return null;
        }

        return $lessons->firstWhere('id', $this->selectedLessonId);
    }

    /**
     * Retorna os valores pedagógicos alternativos da disciplina.
     *
     * @param Collection $lessons
     *
     * @return Collection
     */
    private function pedagogicalFallbacks(Collection $lessons): Collection {
        if ($lessons->isEmpty() || !Schema::hasTable('grade_level_subject')) {
            return collect();
        }

        $availableColumns = collect(['syllabus', 'objectives', 'program_content'])
            ->filter(fn (string $column) => Schema::hasColumn('grade_level_subject', $column))
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

    /**
     * Monta a chave que identifica a disciplina na série.
     *
     * @param int|null $gradeLevelId
     * @param int|null $subjectId
     *
     * @return string
     */
    public function gradeLevelSubjectKey(?int $gradeLevelId, ?int $subjectId): string {
        return "{$gradeLevelId}:{$subjectId}";
    }

    /**
     * Retorna as opções de ano letivo, turma, disciplina e turno dos filtros da agenda.
     *
     * @param mixed $assignments
     *
     * @return array
     */
    private function getFilterOptions($assignments): array {
        $schoolYears = $assignments->pluck('schoolClass.schoolYear')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($y) => [$y->id => $y->year])
            ->all();

        $classes = $assignments->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.($c->schoolYear?->year ?? '—')])
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
            'classes'     => $classes,
            'subjects'    => $subjects,
            'shifts'      => $shifts,
        ];
    }

    /**
     * Recarrega as opções dependentes quando o filtro de ano letivo é alterado.
     *
     * @return void
     */
    public function updatedFilterSchoolYear(): void {
        $this->selectedLessonId = null;
        $year = $this->filterSchoolYear ? SchoolYear::find($this->filterSchoolYear) : null;
        $this->weekOffset = $year && (int) $year->year !== (int) now()->year
            ? (int) now()->startOfWeek()->diffInWeeks($year->starts_at->copy()->startOfWeek(), false)
            : 0;
    }

    /**
     * Recarrega as opções dependentes quando o filtro de turma é alterado.
     *
     * @return void
     */
    public function updatedFilterClass(): void {
        $this->selectedLessonId = null;
    }

    /**
     * Recarrega a agenda quando o filtro de disciplina é alterado.
     *
     * @return void
     */
    public function updatedFilterSubject(): void {
        $this->selectedLessonId = null;
    }

    /**
     * Recarrega a agenda quando o filtro de turno é alterado.
     *
     * @return void
     */
    public function updatedFilterShift(): void {
        $this->selectedLessonId = null;
    }

    /**
     * Restaura os filtros para seus valores padrão.
     *
     * @return void
     */
    public function resetFilters(): void {
        $this->filterSchoolYear = (string) (SchoolYear::current()?->id ?? '');
        $this->weekOffset = 0;
        $this->filterClass      = '';
        $this->filterSubject    = '';
        $this->filterShift      = '';
        $this->selectedLessonId = null;
    }
}
