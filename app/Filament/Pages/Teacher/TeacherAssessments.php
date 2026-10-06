<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\ClassStatus;
use App\Enums\SchoolYearStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Services\CurrentTeacherService;
use App\Support\PermissionAccess;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TeacherAssessments extends Page implements HasTable {

    use HasTeacherPortalAccess;
    use InteractsWithTable;

    protected static ?string $navigationLabel                = 'Avaliações';
    protected static ?string $title                          = 'Avaliações';
    protected static ?string $slug                           = 'teacher-assessments';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-clipboard-question';
    protected static ?int $navigationSort                    = 4;
    protected static ?string $teacherPortalPermission        = 'teacher.assessments.view';

    public ?int $selectedSchoolYearId = null;

    public function mount(): void {
        $this->selectedSchoolYearId = SchoolYear::current()?->id;
    }

    public function updatedSelectedSchoolYearId(): void {
        $this->resetTable();
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.teacher-assessments';
    }

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
    public function getPageData(): array {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $assessments = $this->assessmentQuery($teacher)->get();

        $nextAssessment = $assessments
            ->where('scheduled_at', '>=', now())
            ->sortBy('scheduled_at')
            ->first();

        return [
            'teacher'     => $teacher,
            'assignments' => $assignments,
            'visibleAssignmentCount' => $assignments->filter(fn ($assignment) =>
                (int) $assignment->schoolClass?->school_year_id === (int) $this->selectedSchoolYearId
            )->count(),
            'schoolYears' => $this->schoolYearOptions($teacher),
            'stats'       => [
                'total'  => $assessments->count(),
                'open'   => $assessments->where('status', 'open')->count(),
                'closed' => $assessments->where('status', 'closed')->count(),
                'next'   => $nextAssessment,
            ],
            'canCreate' => $this->canCreateAssessments($teacher, $assignments),
            'isBlocked' => $this->teacherIsBlocked($teacher),
        ];
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table {
        $teacher = $this->currentTeacher();

        return $table
            ->query($this->assessmentQuery($teacher))
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Avaliação')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schoolYearLabel')
                    ->label('Período')
                    ->state(fn (Assessment $record) => $record->schoolClass?->schoolYear?->year ?? $record->schoolYear?->year ?? '—')
                    ->toggleable(),

                TextColumn::make('assessment_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $this->assessmentTypeLabel((string) $state))
                    ->color(fn ($state) => $this->assessmentTypeColor((string) $state)),

                TextColumn::make('max_score')
                    ->label('Máx.')
                    ->numeric(2)
                    ->alignRight(),

                TextColumn::make('weight')
                    ->label('Peso')
                    ->numeric(2)
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $this->assessmentStatusLabel((string) $state))
                    ->color(fn ($state) => $this->assessmentStatusColor((string) $state)),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->options(fn () => $this->classOptions($teacher, false)),

                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn () => $this->subjectOptions($teacher, null, false)),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options($this->assessmentStatusOptions()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Criar avaliação')
                    ->modalHeading('Criar avaliação')
                    ->icon('fas-plus')
                    ->visible(fn () => $this->canCreateAssessments($this->currentTeacher(), app(CurrentTeacherService::class)->currentAssignments()))
                    ->form($this->assessmentFormSchema())
                    ->using(function (array $data) {
                        if (!$this->canCreateAssessments($this->currentTeacher(), app(CurrentTeacherService::class)->currentAssignments())) {
                            throw ValidationException::withMessages(['school_year_id' => 'Você não pode criar avaliações neste ano letivo.']);
                        }

                        return Assessment::create($this->prepareAssessmentPayload($data, null));
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->icon('fas-pen-to-square')
                    ->visible(fn (Assessment $record) => $this->canUpdateAssessment($record))
                    ->form($this->assessmentFormSchema())
                    ->using(function (Assessment $record, array $data) {
                        if (!$this->canUpdateAssessment($record)) {
                            throw ValidationException::withMessages(['assessment' => 'Você não pode editar esta avaliação.']);
                        }

                        $record->update($this->prepareAssessmentPayload($data, $record));

                        return $record;
                    }),

                Action::make('close')
                    ->label('Fechar')
                    ->icon('fas-lock')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Assessment $record) => $this->canCloseAssessment($record))
                    ->action(fn (Assessment $record) => $this->closeAssessment($record)),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    /**
     * Retorna o professor autenticado no portal.
     *
     * @return Teacher|null
     */
    private function currentTeacher(): ?Teacher {
        return app(CurrentTeacherService::class)->current();
    }

    /**
     * Retorna as atribuições do professor autenticado.
     *
     * @param Teacher|null $teacher
     *
     * @return Collection
     */
    private function teacherAssignments(?Teacher $teacher = null): Collection {
        return app(CurrentTeacherService::class)->assignments($teacher);
    }

    /**
     * Retorna a consulta usada para carregar os registros.
     *
     * @param Teacher|null $teacher
     *
     * @return Builder
     */
    private function assessmentQuery(?Teacher $teacher): Builder {
        if (!$teacher) {
            return Assessment::query()->whereRaw('1 = 0');
        }

        return app(CurrentTeacherService::class)->scopeAssignedPairs(Assessment::query(), $this->teacherAssignments($teacher))
            ->forTeacher($teacher->id)
            ->where('school_year_id', $this->selectedSchoolYearId ?? 0)
            ->with(['schoolClass.schoolYear', 'subject', 'teacher']);
    }

    /**
     * Retorna o esquema de campos usado pelo formulário.
     *
     * @return array
     */
    private function assessmentFormSchema(): array {
        return [
            Hidden::make('teacher_id'),
            Hidden::make('school_year_id'),
            Hidden::make('status'),

            Select::make('class_id')
                ->label('Turma')
                ->options(fn () => $this->classOptions($this->currentTeacher()))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('subject_id', null)),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(fn (Get $get) => $this->subjectOptions($this->currentTeacher(), $get('class_id')))
                ->searchable()
                ->required(),

            Select::make('assessment_type')
                ->label('Tipo de avaliação')
                ->options($this->assessmentTypeOptions())
                ->required(),

            DateTimePicker::make('scheduled_at')
                ->label('Data da avaliação')
                ->seconds(false)
                ->required(),

            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(120),

            Textarea::make('description')
                ->label('Descrição')
                ->rows(4)
                ->columnSpanFull(),

            TextInput::make('max_score')
                ->label('Nota máxima')
                ->numeric()
                ->default(10)
                ->minValue(0.01)
                ->required(),

            TextInput::make('weight')
                ->label('Peso')
                ->numeric()
                ->default(1)
                ->minValue(0.01)
                ->required(),
        ];
    }

    /**
     * Prepara os dados usados para salvar a avaliação.
     *
     * @param array $data
     * @param Assessment|null $record
     *
     * @return array
     */
    private function prepareAssessmentPayload(array $data, ?Assessment $record): array {
        $teacher = $this->currentTeacher();

        if (!$teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Nenhum professor ativo foi localizado para o usuário atual.',
            ]);
        }

        $assignments = $this->teacherAssignments($teacher);

        if ($this->teacherIsBlocked($teacher)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Professor afastado, inativo ou desligado não pode criar avaliações.',
            ]);
        }

        if (empty($data['class_id']) || empty($data['subject_id'])) {
            throw ValidationException::withMessages([
                'class_id' => 'Selecione uma turma e uma disciplina válidas.',
            ]);
        }

        $assignment = $assignments->first(function ($item) use ($data) {
            return (int) $item->class_id === (int) $data['class_id']
                && (int) $item->subject_id === (int) $data['subject_id'];
        });

        if (!$assignment) {
            throw ValidationException::withMessages([
                'subject_id' => 'A disciplina selecionada não está vinculada à turma informada para este professor.',
            ]);
        }

        $schoolYear = $assignment->schoolClass?->schoolYear;

        if ($schoolYear?->status !== SchoolYearStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'school_year_id' => 'Não é possível lançar avaliação em período letivo encerrado.',
            ]);
        }

        if ($assignment->schoolClass?->status !== ClassStatus::OPEN) {
            throw ValidationException::withMessages(['class_id' => 'A turma precisa estar aberta para receber avaliações.']);
        }

        $maxScore = (float) ($data['max_score'] ?? 0);

        if ($maxScore <= 0) {
            throw ValidationException::withMessages([
                'max_score' => 'A nota máxima precisa ser maior que zero.',
            ]);
        }

        if ($record?->isClosed()) {
            throw ValidationException::withMessages([
                'status' => 'Avaliação fechada não pode ser editada.',
            ]);
        }

        return [
            'teacher_id'      => $teacher->id,
            'school_year_id'  => $schoolYear?->id,
            'class_id'        => (int) $data['class_id'],
            'subject_id'      => (int) $data['subject_id'],
            'title'           => trim((string) ($data['title'] ?? '')),
            'description'     => $data['description'] ?? null,
            'assessment_type' => $data['assessment_type'] ?? 'outro',
            'date'            => isset($data['scheduled_at'])
                ? Carbon::parse($data['scheduled_at'])->toDateString()
                : null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'max_score'    => $maxScore,
            'weight'       => (float) ($data['weight'] ?? 1),
            'status'       => $record?->status ?? 'open',
        ];
    }

    /**
     * Encerra a avaliação e bloqueia novos lançamentos.
     *
     * @param Assessment $record
     *
     * @return void
     */
    private function closeAssessment(Assessment $record): void {
        if (!$this->canCloseAssessment($record)) {
            throw ValidationException::withMessages([
                'status' => 'Você não tem permissão para fechar esta avaliação.',
            ]);
        }

        $record->update([
            'status' => 'closed',
        ]);
    }

    /**
     * Determina se o professor pode criar avaliações.
     *
     * @param Teacher|null $teacher
     * @param Collection $assignments
     *
     * @return bool
     */
    private function canCreateAssessments(?Teacher $teacher, Collection $assignments): bool {
        return PermissionAccess::can('teacher.assessments.create')
            && $teacher !== null
            && !$this->teacherIsBlocked($teacher)
            && $this->selectedSchoolYearId === SchoolYear::current()?->id
            && $assignments->contains(fn ($assignment) =>
                $assignment->schoolClass?->schoolYear?->status === SchoolYearStatus::ACTIVE
                && $assignment->schoolClass?->status === ClassStatus::OPEN
            );
    }

    /**
     * Determina se o professor pode atualizar a avaliação informada.
     *
     * @param Assessment $record
     *
     * @return bool
     */
    private function canUpdateAssessment(Assessment $record): bool {
        $teacher = $this->currentTeacher();

        return PermissionAccess::can('teacher.assessments.update')
            && $teacher !== null
            && $teacher->canAccessOperationally()
            && (int) $record->teacher_id === (int) $teacher->id
            && $record->schoolYear?->status === SchoolYearStatus::ACTIVE
            && $record->schoolClass?->status === ClassStatus::OPEN
            && $this->teacherAssignments($teacher)->contains(fn ($assignment) =>
                (int) $assignment->class_id === (int) $record->class_id
                && (int) $assignment->subject_id === (int) $record->subject_id)
            && !$record->isClosed();
    }

    /**
     * Determina se o professor pode encerrar a avaliação informada.
     *
     * @param Assessment $record
     *
     * @return bool
     */
    private function canCloseAssessment(Assessment $record): bool {
        $teacher = $this->currentTeacher();

        return PermissionAccess::can('teacher.assessments.close')
            && $teacher !== null
            && $teacher->canAccessOperationally()
            && (int) $record->teacher_id === (int) $teacher->id
            && $record->schoolYear?->status === SchoolYearStatus::ACTIVE
            && $record->schoolClass?->status === ClassStatus::OPEN
            && $this->teacherAssignments($teacher)->contains(fn ($assignment) =>
                (int) $assignment->class_id === (int) $record->class_id
                && (int) $assignment->subject_id === (int) $record->subject_id)
            && !$record->isClosed();
    }

    /**
     * Determina se o professor está impedido de realizar lançamentos.
     *
     * @param Teacher|null $teacher
     *
     * @return bool
     */
    private function teacherIsBlocked(?Teacher $teacher): bool {
        if (!$teacher) {
            return true;
        }

        return !$teacher->canAccessOperationally();
    }

    /**
     * Retorna as turmas disponíveis para o cadastro de avaliações.
     *
     * @param Teacher|null $teacher
     *
     * @return array
     */
    private function classOptions(?Teacher $teacher, bool $currentOnly = true): array {
        return ($currentOnly
            ? app(CurrentTeacherService::class)->currentAssignments($teacher)
            : $this->teacherAssignments($teacher))
            ->pluck('schoolClass')
            ->filter()
            ->when($currentOnly, fn (Collection $classes) => $classes->filter(fn (SchoolClass $class) => $class->status === ClassStatus::OPEN))
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($class) => [
                $class->id => trim($class->name . ' - ' . ($class->schoolYear?->year ?? $class->schoolYear?->name ?? 'Sem período')),
            ])
            ->all();
    }

    /**
     * Retorna as disciplinas disponíveis na turma selecionada.
     *
     * @param Teacher|null $teacher
     * @param mixed $classId
     *
     * @return array
     */
    private function subjectOptions(?Teacher $teacher, $classId = null, bool $currentOnly = true): array {
        $assignments = $currentOnly
            ? app(CurrentTeacherService::class)->currentAssignments($teacher)
            : $this->teacherAssignments($teacher);

        if ($currentOnly) {
            $assignments = $assignments->filter(fn ($assignment) => $assignment->schoolClass?->status === ClassStatus::OPEN);
        }

        if ($classId) {
            $assignments = $assignments->where('class_id', (int) $classId);
        }

        return $assignments
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($subject) => [
                $subject->id => $subject->name,
            ])
            ->all();
    }

    /**
     * Retorna os anos letivos disponíveis nas atribuições do professor.
     *
     * @param Teacher|null $teacher
     *
     * @return array
     */
    private function schoolYearOptions(?Teacher $teacher): array {
        return $this->teacherAssignments($teacher)
            ->pluck('schoolClass.schoolYear')
            ->filter()
            ->unique('id')
            ->sortByDesc('year')
            ->mapWithKeys(fn ($schoolYear) => [
                $schoolYear->id => $schoolYear->year ?? $schoolYear->name,
            ])
            ->all();
    }

    /**
     * Retorna o identificador do ano letivo associado à turma.
     *
     * @param mixed $classId
     *
     * @return int|null
     */
    private function schoolYearIdForClass($classId): ?int {
        if (!$classId) {
            return null;
        }

        return SchoolClass::query()
            ->with('schoolYear')
            ->find($classId)?->schoolYear?->id;
    }

    /**
     * Retorna os status disponíveis para uma avaliação.
     *
     * @return array
     */
    private function assessmentStatusOptions(): array {
        return [
            'open'   => 'Aberta',
            'closed' => 'Fechada',
        ];
    }

    /**
     * Retorna o rótulo usado para exibir o status da avaliação.
     *
     * @param string $status
     *
     * @return string
     */
    private function assessmentStatusLabel(string $status): string {
        return $this->assessmentStatusOptions()[$status] ?? ucfirst($status);
    }

    /**
     * Retorna a cor usada para exibir o status da avaliação.
     *
     * @param string $status
     *
     * @return string
     */
    private function assessmentStatusColor(string $status): string {
        return match ($status) {
            'open'   => 'success',
            'closed' => 'gray',
            default  => 'gray',
        };
    }

    /**
     * Retorna os tipos de avaliação disponíveis para seleção.
     *
     * @return array
     */
    private function assessmentTypeOptions(): array {
        return [
            'test'        => 'Prova',
            'work'        => 'Trabalho',
            'activity'    => 'Atividade',
            'seminar'     => 'Seminário',
            'project'     => 'Projeto',
            'recovery'    => 'Recuperação',
            'outro'       => 'Outro',
        ];
    }

    /**
     * Retorna o rótulo usado para exibir o tipo de avaliação.
     *
     * @param string $type
     *
     * @return string
     */
    private function assessmentTypeLabel(string $type): string {
        return $this->assessmentTypeOptions()[$type] ?? match ($type) {
            'prova' => 'Prova',
            'trabalho' => 'Trabalho',
            'atividade' => 'Atividade',
            'projeto' => 'Projeto',
            'seminario' => 'Seminário',
            'recuperacao' => 'Recuperação',
            default => ucfirst($type),
        };
    }

    /**
     * Retorna a cor usada para exibir o tipo de avaliação.
     *
     * @param string $type
     *
     * @return string
     */
    private function assessmentTypeColor(string $type): string {
        return match ($type) {
            'test', 'prova' => 'danger',
            'work', 'trabalho' => 'warning',
            'activity', 'atividade' => 'info',
            'seminar', 'seminario' => 'primary',
            'project', 'projeto' => 'success',
            'recovery', 'recuperacao' => 'gray',
            default       => 'gray',
        };
    }
}
