<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';

    protected static ?string $title = 'Disciplinas da Turma';

    /**
     * Configura a tabela e suas ações.
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withTrashed())
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Disciplina')
                    ->formatStateUsing(fn (?string $state, $record): string => ($state ?? 'Indisponível')
                        .($record->trashed() ? ' · Inativo' : ''))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categoryLabel')
                    ->label('Componente')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('professores')
                    ->label('Professor(es)')
                    ->state(function ($record) {
                        $class = $this->getOwnerRecord();

                        /* teacherAssignments da turma filtrados pela disciplina atual. */
                        $assignments = $class->teacherAssignments()
                            ->where('subject_id', $record->id)
                            ->with(['teacher' => fn ($query) => $query->withTrashed()])
                            ->get();

                        return $assignments
                            ->map(fn ($a) => $a->teacher
                                ? $a->teacher->name.($a->teacher->trashed() ? ' · Inativo' : '')
                                : null)
                            ->filter()
                            ->values()
                            ->all();
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_ends_with($state, '· Inativo') ? 'gray' : 'primary')
                    ->toggleable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Adicionar disciplina')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['code', 'name'])
                    ->recordTitleAttribute('name')
                    ->recordSelect(fn (Select $select): Select => $select
                        ->label('Disciplina')
                        ->placeholder('Selecione uma disciplina'))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('teacher_id')
                            ->label('Professor')
                            ->options(fn () => Teacher::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Atribuir depois, se necessário'),
                    ])
                    ->after(function ($record, array $data) {
                        /** @var SchoolClass $class */
                        $class = $this->getOwnerRecord();

                        if (blank($data['teacher_id'] ?? null)) {
                            return;
                        }

                        $teacher = Teacher::findOrFail($data['teacher_id']);

                        app(TeacherOnboardingService::class)->createAssignment($teacher, [
                            'class_id' => $class->id,
                            'subject_id' => $record->id,
                        ]);
                    }),
            ])

            ->actions([
                EditAction::make()
                    ->label('Editar professor')
                    ->form([
                        Select::make('teacher_id')
                            ->label('Professor')
                            ->options(fn () => Teacher::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->fillForm(function ($record): array {
                        $class = $this->getOwnerRecord();

                        $assignment = $class->teacherAssignments()
                            ->where('subject_id', $record->id)
                            ->first();

                        return [
                            'teacher_id' => $assignment?->teacher_id,
                        ];
                    })
                    ->using(function ($record, array $data) {
                        $class = $this->getOwnerRecord();

                        $assignment = TeacherAssignment::query()
                            ->where('class_id', $class->id)
                            ->where('subject_id', $record->id)
                            ->first();
                        $teacher = Teacher::findOrFail($data['teacher_id']);

                        if ($assignment) {
                            app(TeacherOnboardingService::class)
                                ->updateAssignment($assignment, [], $teacher);

                            return;
                        }

                        app(TeacherOnboardingService::class)->createAssignment($teacher, [
                            'class_id' => $class->id,
                            'subject_id' => $record->id,
                        ]);
                    }),

                DetachAction::make()
                    ->label('Remover')
                    ->after(function ($record) {
                        $class = $this->getOwnerRecord();

                        TeacherAssignment::where('class_id', $class->id)
                            ->where('subject_id', $record->id)
                            ->delete();
                    }),
            ]);
    }
}
