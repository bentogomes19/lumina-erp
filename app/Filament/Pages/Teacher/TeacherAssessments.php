<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\SchoolYearStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
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
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherAssessments extends Page implements HasTable
{
    use HasTeacherPortalAccess;
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Avaliações';
    protected static ?string $title = 'Avaliações';
    protected static ?string $slug = 'teacher-assessments';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-clipboard-question';
    protected static ?int $navigationSort = 4;
    protected static ?string $teacherPortalPermission = 'teacher.assessments.view';

    public function getView(): string
    {
        return 'filament.pages.teacher.teacher-assessments';
    }

    public function getPageData(): array
    {
        $teacher = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $assessments = $this->assessmentQuery($teacher)->get();

        $nextAssessment = $assessments
            ->where('scheduled_at', '>=', now())
            ->sortBy('scheduled_at')
            ->first();

        return [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'stats' => [
                'total' => $assessments->count(),
                'open' => $assessments->where('status', 'open')->count(),
                'closed' => $assessments->where('status', 'closed')->count(),
                'next' => $nextAssessment,
            ],
            'canCreate' => $this->canCreateAssessments($teacher, $assignments),
            'isBlocked' => $this->teacherIsBlocked($teacher),
        ];
    }

    public function table(Table $table): Table
    {
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
                    ->options(fn () => $this->classOptions($teacher)),

                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn () => $this->subjectOptions($teacher)),

                SelectFilter::make('school_year_id')
                    ->label('Período')
                    ->options(fn () => $this->schoolYearOptions($teacher)),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options($this->assessmentStatusOptions()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Criar avaliação')
                    ->icon('fas-plus')
                    ->visible(fn () => $this->canCreateAssessments($this->currentTeacher(), $this->teacherAssignments()))
                    ->form($this->assessmentFormSchema())
                    ->using(function (array $data) {
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

    private function currentTeacher(): ?Teacher
    {
        return app(CurrentTeacherService::class)->current();
    }

    private function teacherAssignments(?Teacher $teacher = null): Collection
    {
        return app(CurrentTeacherService::class)->assignments($teacher);
    }

    private function assessmentQuery(?Teacher $teacher): Builder
    {
        if (! $teacher) {
            return Assessment::query()->whereRaw('1 = 0');
        }

        return Assessment::query()
            ->forTeacher($teacher->id)
            ->with(['schoolClass.schoolYear', 'subject', 'teacher']);
    }

    private function assessmentFormSchema(): array
    {
        return [
            Hidden::make('teacher_id'),
            Hidden::make('school_year_id'),
            Hidden::make('status'),

            Select::make('class_id')
                ->label('Turma')
                ->options(fn () => $this->classOptions($this->currentTeacher()))
                ->searchable()
                ->required()
                ->live(),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(fn () => $this->subjectOptions($this->currentTeacher()))
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

    private function prepareAssessmentPayload(array $data, ?Assessment $record): array
    {
        $teacher = $this->currentTeacher();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Nenhum professor ativo foi localizado para o usuário atual.',
            ]);
        }

        $assignments = $this->teacherAssignments($teacher);

        if ($record === null && $this->teacherIsBlocked($teacher)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Professor afastado, inativo ou desligado não pode criar avaliações.',
            ]);
        }

        if (! $data['class_id'] || ! $data['subject_id']) {
            throw ValidationException::withMessages([
                'class_id' => 'Selecione uma turma e uma disciplina válidas.',
            ]);
        }

        $assignment = $assignments->first(function ($item) use ($data) {
            return (int) $item->class_id === (int) $data['class_id']
                && (int) $item->subject_id === (int) $data['subject_id'];
        });

        if (! $assignment) {
            throw ValidationException::withMessages([
                'subject_id' => 'A disciplina selecionada não está vinculada à turma informada para este professor.',
            ]);
        }

        $schoolYear = $assignment->schoolClass?->schoolYear;

        if ($schoolYear?->status === SchoolYearStatus::CLOSED) {
            throw ValidationException::withMessages([
                'school_year_id' => 'Não é possível lançar avaliação em período letivo encerrado.',
            ]);
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
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear?->id,
            'class_id' => (int) $data['class_id'],
            'subject_id' => (int) $data['subject_id'],
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => $data['description'] ?? null,
            'assessment_type' => $data['assessment_type'] ?? 'outro',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'max_score' => $maxScore,
            'weight' => (float) ($data['weight'] ?? 1),
            'status' => $data['status'] ?? 'open',
        ];
    }

    private function closeAssessment(Assessment $record): void
    {
        if (! $this->canCloseAssessment($record)) {
            throw ValidationException::withMessages([
                'status' => 'Você não tem permissão para fechar esta avaliação.',
            ]);
        }

        $record->update([
            'status' => 'closed',
        ]);
    }

    private function canCreateAssessments(?Teacher $teacher, Collection $assignments): bool
    {
        return PermissionAccess::can('teacher.assessments.create')
            && $teacher !== null
            && ! $this->teacherIsBlocked($teacher)
            && $assignments->isNotEmpty();
    }

    private function canUpdateAssessment(Assessment $record): bool
    {
        $teacher = $this->currentTeacher();

        return PermissionAccess::can('teacher.assessments.update')
            && $teacher !== null
            && (int) $record->teacher_id === (int) $teacher->id
            && ! $record->isClosed();
    }

    private function canCloseAssessment(Assessment $record): bool
    {
        $teacher = $this->currentTeacher();

        return PermissionAccess::can('teacher.assessments.close')
            && $teacher !== null
            && (int) $record->teacher_id === (int) $teacher->id
            && ! $record->isClosed();
    }

    private function teacherIsBlocked(?Teacher $teacher): bool
    {
        if (! $teacher) {
            return true;
        }

        return in_array($teacher->status, [
            TeacherStatus::SABBATICAL,
            TeacherStatus::INACTIVE,
            TeacherStatus::TERMINATED,
        ], true);
    }

    private function classOptions(?Teacher $teacher): array
    {
        return $this->teacherAssignments($teacher)
            ->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($class) => [
                $class->id => trim($class->name . ' - ' . ($class->schoolYear?->year ?? $class->schoolYear?->name ?? 'Sem período')),
            ])
            ->all();
    }

    private function subjectOptions(?Teacher $teacher, $classId = null): array
    {
        $assignments = $this->teacherAssignments($teacher);

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

    private function schoolYearOptions(?Teacher $teacher): array
    {
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

    private function schoolYearIdForClass($classId): ?int
    {
        if (! $classId) {
            return null;
        }

        return SchoolClass::query()
            ->with('schoolYear')
            ->find($classId)?->schoolYear?->id;
    }

    private function assessmentStatusOptions(): array
    {
        return [
            'open' => 'Aberta',
            'closed' => 'Fechada',
        ];
    }

    private function assessmentStatusLabel(string $status): string
    {
        return $this->assessmentStatusOptions()[$status] ?? ucfirst($status);
    }

    private function assessmentStatusColor(string $status): string
    {
        return match ($status) {
            'open' => 'success',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    private function assessmentTypeOptions(): array
    {
        return [
            'prova' => 'Prova',
            'trabalho' => 'Trabalho',
            'atividade' => 'Atividade',
            'seminario' => 'Seminário',
            'projeto' => 'Projeto',
            'recuperacao' => 'Recuperação',
            'outro' => 'Outro',
        ];
    }

    private function assessmentTypeLabel(string $type): string
    {
        return $this->assessmentTypeOptions()[$type] ?? ucfirst($type);
    }

    private function assessmentTypeColor(string $type): string
    {
        return match ($type) {
            'prova' => 'danger',
            'trabalho' => 'warning',
            'atividade' => 'info',
            'seminario' => 'primary',
            'projeto' => 'success',
            'recuperacao' => 'gray',
            default => 'gray',
        };
    }
}