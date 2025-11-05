<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';
    protected static ?string $title = 'Disciplinas da Turma';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Disciplina')
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

                        // teacherAssignments da turma filtrados pela disciplina atual
                        $assignments = $class->teacherAssignments()
                            ->where('subject_id', $record->id)
                            ->with('teacher')
                            ->get();

                        return $assignments
                            ->map(fn($a) => $a->teacher?->name)
                            ->filter()
                            ->join(', ');
                    })
                    ->toggleable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Adicionar disciplina')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['code', 'name'])
                    ->recordTitleAttribute('name')
                    ->form([
                        Select::make('teacher_id')
                            ->label('Professor')
                            ->options(fn () => Teacher::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->after(function ($record, array $data) {
                        /** @var \App\Models\SchoolClass $class */
                        $class = $this->getOwnerRecord();

                        // 🔍 Já existe professor para (turma, disciplina)?
                        $exists = TeacherAssignment::where('class_id', $class->id)
                            ->where('subject_id', $record->id)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Já existe um professor vinculado a esta disciplina nesta turma.')
                                ->body('Remova ou edite o vínculo atual antes de cadastrar outro professor.')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Se não existe, cria o vínculo
                        TeacherAssignment::create([
                            'class_id'   => $class->id,
                            'subject_id' => $record->id,
                            'teacher_id' => $data['teacher_id'],
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

                        // aqui pode trocar o professor livremente,
                        // porque continua 1 disciplina / 1 turma / 1 professor
                        TeacherAssignment::updateOrCreate(
                            [
                                'class_id'   => $class->id,
                                'subject_id' => $record->id,
                            ],
                            [
                                'teacher_id' => $data['teacher_id'],
                            ],
                        );
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
